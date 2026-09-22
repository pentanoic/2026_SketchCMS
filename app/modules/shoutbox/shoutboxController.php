<?php

/**
 * Software: SketchCMS
 * Author: valedrat
 * Email: kioku17@protonmail.com
 * GitHub: https://github.com/pentanoic/
 * Website: https://lab302.ovh/
 *
 * Copyright (c) 2026 valedrat. All rights reserved.
 *
 * This file is part of the SketchCMS source code.
 * Please do not remove or modify this copyright notice.
 */

class shoutboxController extends Controller
{
    /*
     * Note: giải thích cách hoạt động của cái chat này - éo ghi lại sao này éo nhớ nổi
     * * 1. load all chat ở chat_list
     * * 2. lấy tổng ID chat hiện tại - totalChat
     * * 3. Auto refesh kiểm tra tổng số chat hiện tại - nowChat - fetch ở chat_count
     * * 4. Nếu nowChat lớn hơn totalChat thì thêm các chat mới - từ chat_ele
     * * 5. xóa phần tử chat cuối cùng
     * * A. Ưu điểm của chat: có thể xem được video mới, có thể copy được nội dung chat trên điện thoại
     * * B. Bug đã biết: chat nhanh quá thì bị cập nhật sai =))
     * Dai (agwinao) 2-2-2022 ~ code từ mùng 1 đến mùng 2 Tết
     */

    protected $ChatCount = 0;

    private articlesModel $articlesModel;
    private userModel $userModel;
    private shoutboxModel $shoutboxModel;
    private articlesLibrary $articlesLibrary;

    function __construct()
    {
        parent::__construct();
        $this->articlesModel = $this->load->model('articles');
        $this->userModel = $this->load->model('user');
        $this->shoutboxModel = $this->load->model('shoutbox');
        $this->articlesLibrary = $this->load->library('articles');
        $this->ChatCount = $this->articlesModel->ForumStats()['count_chat'];
    }

    public function ChatSend()
    {
        $result = [];
        $error = false;
        if (!$this->request->user()->isLogin) {
            $error = true;
            $result = ['status' => 'error', 'result' => 'Chức năng này chỉ dành cho người dùng đã đăng nhập!'];
        }

        $msg = $this->request->postVar('msg', '');
        $msg = $this->articlesLibrary->TrimContent($msg);
        $msg_len = $this->articlesLibrary->ContentLen($msg);
        if (!$error) {
            $user_id = $this->request->user()->id;
            $user = $this->userModel->UserDetailWithFields('id', $user_id);

            $systemConfig = config('system');
            $sysRateLimit = isset($systemConfig['security']['rate_limit']) ? (int)$systemConfig['security']['rate_limit'] : 1;

            // Admin và Mod không bị giới hạn thời gian (dựa trên bài học phân quyền)
            if (isset($user['level']) && $user['level'] >= 100) {
                $timeLimit = 0;
            } else {
                // Áp dụng Rate Limit của hệ thống + thêm ngẫu nhiên 1-3 giây để chống Bot spam theo chu kỳ
                $timeLimit = $sysRateLimit > 0 ? $sysRateLimit + rand(1, 3) : 0;
            }

            $timeSet = time() + $timeLimit;
            $timeLimitUser = $user['time_shoutbox'] - time();

            if (!isset($msg)) {
                $result = ['status' => 'error', 'result' => 'Vui lòng nhập nội dung!'];
            } elseif ($msg_len < 5 || $msg_len > 1200) {
                $result = ['status' => 'error', 'result' => 'Độ dài văn bản được nhập không hợp lệ'];
            } elseif ($timeLimit > 0 && time() < $user['time_shoutbox'] && $user['time_shoutbox'] != 0) {
                $result = ['status' => 'error', 'result' => 'Bạn gửi tin nhắn quá nhanh. Vui lòng chờ ' . $timeLimitUser . ' giây'];
            } else {
                // Mã hóa E2E
                $e2e = new E2E();
                $encrypted_msg = $e2e->encrypt($msg, E2E_SECRET_KEY, 'shoutbox' . $user['nick']);
                $this->shoutboxModel->ChatSend($user, $encrypted_msg);

                $this->userModel->UserDetailEdit($user, ['time_shoutbox' => $timeSet]);
                $result = ['status' => 'success', 'result' => 'Đã gửi'];
            }
        }

        return $result;
    }

    public function ChatHome()
    {
        $this->userModel->isLoginredirect($this->request);
        return view('shoutbox/chat_home', [
            'chat_count' => $this->ChatCount
        ]);
    }

    public function ChatCount()
    {
        return view('shoutbox/chat_count', [
            'chat_count' => $this->ChatCount
        ]);
    }

    public function ChatEle()
    {
        $id_chat = $this->request->getVar('chatID', 1);
        $ChatDetail = $this->shoutboxModel->ChatDetail($id_chat);
        // Giải mã E2E
        $e2e = new E2E();
        if ($e2e->isEncrypted($ChatDetail['comment'], E2E_SECRET_KEY, 'shoutbox' . $ChatDetail['name'])) {
            $ChatDetail['comment'] = $e2e->decrypt($ChatDetail['comment'], E2E_SECRET_KEY, 'shoutbox' . $ChatDetail['name']);
        } else {
            $ChatDetail['comment'] = "<i>Nội dung này chưa mã hóa đầu cuối nên không thể hiển thị</i>";
        }

        $ChatDetail['comment'] = $this->articlesLibrary->bbcode($ChatDetail['comment']);
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $ChatDetail['name']);
        $UserDetail['online_status'] = $this->userModel->UserOnlineStatus($UserDetail);
        return view('shoutbox/chat_ele', [
            'ChatDetail' => $ChatDetail,
            'UserDetail' => $UserDetail
        ]);
    }

    public function ChatList()
    {
        $ChatCount = $this->articlesModel->ForumStats()['count_chat'];
        # số bài viết có trong 1 trang
        $per = 10;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        $page_max = ceil($ChatCount / $per);
        if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
            $page = 1;
        } elseif ($page >= $page_max && $page_max > 0) {
            $page = $page_max;
        }
        if ($page < 1) $page = 1;

        $start = ($page - 1) * $per;
        $limit = $per;

        # lấy danh sách comment
        $getChatList = $this->shoutboxModel->ChatList($start, $limit);
        $ChatList = [];
        foreach ($getChatList as $ChatDetail) {
            $name = mb_strtolower($ChatDetail['name']);
            $ChatDetail['UserDetail'] = $this->userModel->UserDetailWithFields('nick', $name);
            $ChatDetail['UserDetail']['online_status'] = $this->userModel->UserOnlineStatus($ChatDetail['UserDetail']);

            // Giải mã E2E
            $e2e = new E2E();
            if ($e2e->isEncrypted($ChatDetail['comment'], E2E_SECRET_KEY, 'shoutbox' . $ChatDetail['name'])) {
                $ChatDetail['comment'] = $e2e->decrypt($ChatDetail['comment'], E2E_SECRET_KEY, 'shoutbox' . $ChatDetail['name']);
            } else {
                $ChatDetail['comment'] = "<i>Nội dung này chưa mã hóa đầu cuối nên không thể hiển thị</i>";
            }

            $ChatDetail['comment'] = $this->articlesLibrary->bbcode($ChatDetail['comment']);

            $ChatList[] = $ChatDetail;
        }

        return view('shoutbox/chat_list', [
            'ChatList' => $ChatList
        ]);
    }
}
