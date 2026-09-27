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

class mailController extends Controller
{
    private userModel $userModel;
    private mailModel $mailModel;
    private articlesLibrary $articlesLibrary;

    function __construct()
    {
        parent::__construct();
        $this->userModel = $this->load->model('user');
        $this->mailModel = $this->load->model('mail');
        $this->articlesLibrary = $this->load->library('articles');
    }

    public function MailList()
    {
        if (!$this->request->user()->isLogin) {
            redirect('/');
        }
        $MyDetail = $this->request->user()->user;
        // lấy danh sách tin nhắn đã gửi đến các Receiver của Sender từ bảng `users`
        $getMailList = $this->mailModel->MailList($MyDetail);
        // phân trang
        $page_query = '?page=';
        $MailCount = count($getMailList);
        $per = 10;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        $page_max = ceil($MailCount / $per);
        $start = ($page - 1) * $per;
        // lấy danh sách Receiver của Sender
        $getReceiverList = array_slice($getMailList, $start, $per);
        $MailList = [];
        foreach ($getReceiverList as $Receiver) {
            $Receiver = mb_strtolower($Receiver);
            $MailDetail['ReceiverDetail'] = $this->userModel->UserDetailWithFields('nick', $Receiver);
            if ($MailDetail['ReceiverDetail']) {
            $LatestMailDetail = $this->mailModel->LatestMailDetail($MyDetail['nick'], $Receiver);
            $MailDetail['LatestMailDetail'] = $LatestMailDetail;
            $LastSender = mb_strtolower($LatestMailDetail['nick']);
            //die($LastSender);
            $MailDetail['SenderDetail'] = $this->userModel->UserDetailWithFields('nick', $LastSender);
            $lastContent = $LatestMailDetail['content'];
            // Giải mã E2E
            $e2e = new E2E();
            if ($e2e->isEncrypted($lastContent, E2E_SECRET_KEY, 'mail' . $LastSender)) {
                $lastContent = $e2e->decrypt($lastContent, E2E_SECRET_KEY, 'mail' . $LastSender);
            } else {
                $lastContent = "<i>Nội dung này chưa mã hóa đầu cuối nên không thể hiển thị</i>";
            }
            
            $slicedContent = substr($lastContent, 0, 150);
            if (strlen($lastContent) > 150) {
                $slicedContent .= '...';
            }
            $MailDetail['LatestMailDetail']['content'] = $slicedContent;
            $MailDetail['MailCount'] = $this->mailModel->MailCount($MyDetail['nick'], $Receiver);

            $MailList[] = $MailDetail;
            }
        }
        $MailListPaging = paging($page_query, $page, $page_max);

        return view()
            ->setTitle('Tin nhắn')
            ->render('mail/mail_list', [
                'MyDetail' => $MyDetail,
                'MailList' => $MailList,
                'MailListPaging' => $MailListPaging,
                'MailCount' => $MailCount
            ]);
    }

    public function MailSend($uri_receiver, )
    {
        if (!$this->request->user()->isLogin) {
            redirect('/');
        }

        // lấy thông tin người gửi và người nhận
        $uri_receiver = mb_strtolower($uri_receiver);
        $SenderDetail = $this->request->user()->user;
        $ReceiverDetail = $this->userModel->UserDetailWithFields('nick', $uri_receiver);
        if (!$ReceiverDetail || $SenderDetail['nick'] == $ReceiverDetail['nick']) {
            redirect('/404');
        }
        // lấy blocklist của người gửi và người nhận
        $SenderDetailBlockList = $this->userModel->UserDetailBlockList($SenderDetail)['Get'];
        $ReceiverDetailBlockList = $this->userModel->UserDetailBlockList($ReceiverDetail)['Get'];
        // block khi ngứa mắt
        $mod = $this->request->getVar('mod', '');
        $isSenderBlock = false;
        $isReceiverBlock = false;
        if (
            in_array($SenderDetail['nick'], $ReceiverDetailBlockList)
            || $ReceiverDetail['nick'] == UserBot
        ) {
            $isReceiverBlock = true;
        }
        if (in_array($ReceiverDetail['nick'], $SenderDetailBlockList)) {
            $isSenderBlock = true;
        }
        if ($mod == 'blocklist') {
            $this->mailModel->MailBlocked($SenderDetail, $ReceiverDetail);
            redirect('/mail/send/' . $ReceiverDetail['nick']);
        }

        // phân trang
        $MailCount = $this->mailModel->MailCount($SenderDetail['nick'], $ReceiverDetail['nick']);
        $per = 10;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
            $page = 1;
        }
        $page_max = ceil($MailCount / $per);
        $start = ($page - 1) * $per;

        $getMailList = $this->mailModel->MailDetailList($SenderDetail['nick'], $ReceiverDetail['nick'], $per, $start);
        $MailList = [];
        foreach ($getMailList as $MailDetail) {
            $nick = mb_strtolower($MailDetail['nick']);
            $MailDetail['UserDetail'] = $this->userModel->UserDetailWithFields('nick', $nick);
            
            // Giải mã E2E
            $e2e = new E2E();
            if ($e2e->isEncrypted($MailDetail['content'], E2E_SECRET_KEY, 'mail' . $MailDetail['nick'])) {
                $MailDetail['content'] = $e2e->decrypt($MailDetail['content'], E2E_SECRET_KEY, 'mail' . $MailDetail['nick']);
            } else {
                $MailDetail['content'] = "<i>Nội dung này chưa mã hóa đầu cuối nên không thể hiển thị</i>";
            }
            
            $MailDetail['content'] = $this->articlesLibrary->bbcode($MailDetail['content']);

            $MailList[] = $MailDetail;
        }
        $MailPaging = paging('?page=', $page, $page_max);

        // Xử lý tin nhắn được gửi đi
        $error = null;
        $content = $this->request->postVar('content', '');
        $content = $this->articlesLibrary->TrimContent($content);
        $content_len = $this->articlesLibrary->ContentLen($content);
        if (!$isSenderBlock && !$isReceiverBlock) {
            if ($this->request->getMethod() === 'POST') {
                // reset token
                $token = $this->request->postVar('csrf_token', '');
                $checktoken = isCSRFTokenValid($token);
                if ($checktoken) {
                    $error = 'Invalid token';
                }
                unsetCSRFToken();
                generateCSRFToken();

                if (empty($content)) {
                    $error = 'Tin nhắn gửi đi không được bỏ trống';
                }
                if ($content_len < 4 || $content_len > 1200) {
                    $error = 'Độ dài văn bản không hợp lệ';
                }
                if (!$error) {
                    // Mã hóa E2E
                    $e2e = new E2E();
                    $encrypted_content = $e2e->encrypt($content, E2E_SECRET_KEY, 'mail' . $SenderDetail['nick']);
                    
                    $this->mailModel->MailSend($SenderDetail, $ReceiverDetail, $encrypted_content);
                    redirect($_SERVER['REQUEST_URI']);
                }
            }
        }

        // đánh dấu là đã đọc
        $viewClosure = function ($mail_id) {
            $self = $this;
            return $self->mailModel->MailViewUpdate($mail_id);
        };

        return view()
            ->setTitle('Tin nhắn: ' . $ReceiverDetail['name'])
            ->render('mail/mail_send', [
                'SenderDetail' => $SenderDetail,
                'ReceiverDetail' => $ReceiverDetail,
                'SenderDetailBlockList' => $SenderDetailBlockList,
                'ReceiverDetailBlockList' => $ReceiverDetailBlockList,
                'MailList' => $MailList,
                'MailPaging' => $MailPaging,

                'error' => $error,
                'content' => $content,
                'view' => $viewClosure,

                'isSenderBlock' => $isSenderBlock,
                'isReceiverBlock' => $isReceiverBlock
            ]);
    }

    public function MailSystem()
    {
        if (!$this->request->user()->isLogin) {
            redirect('/');
        }
        $MyDetail = $this->request->user()->user;

        // phân trang
        //$SystemNotifyCount = $this->request->user()->system_notify_count;
        $SystemNotifyCount = $this->mailModel->MailSystemCount($MyDetail);
        $per = 10;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
            $page = 1;
        }
        $page_max = ceil($SystemNotifyCount / $per);
        $start = ($page - 1) * $per;

        // lấy dữ liệu từ model
        $MailSystemListPaging = paging('?page=', $page, $page_max);
        $getMailSystemList = $this->mailModel->MailSystemList($MyDetail, $per, $start);
        $MailSystemList = [];
        $e2e = new E2E();
        foreach($getMailSystemList as $MailSystemDetail) {
            // Giải mã E2E
            if ($e2e->isEncrypted($MailSystemDetail['content'], E2E_SECRET_KEY, 'mail' . $MailSystemDetail['nick'])) {
                $MailSystemDetail['content'] = $e2e->decrypt($MailSystemDetail['content'], E2E_SECRET_KEY, 'mail' . $MailSystemDetail['nick']);
            } else {
                $MailSystemDetail['content'] = "<i>Nội dung này chưa mã hóa đầu cuối nên không thể hiển thị</i>";
            }
            
            $MailSystemDetail['content'] = $this->articlesLibrary->bbcode($MailSystemDetail['content']);
            $MailSystemList[] = $MailSystemDetail;
        }

        // thao tác đánh dấu đã xem và xóa sạch
        $mod = $this->request->getVar('mod', '');
        if ($mod == 'view') {
            // đánh dấu là đã xem
            $this->mailModel->MailSystemView($MyDetail);
            redirect('/mail/system');
        } elseif ($mod == 'clear') {
            // xóa sạch
            $this->mailModel->MailSystemClear($MyDetail);
            redirect('/mail/system');
        }

        // return vỉew
        return view()
            ->setTitle('Thông báo')
            ->render('mail/mail_system', [
                'MyDetail' => $MyDetail,
                'MailSystemList' => $MailSystemList,
                'MailSystemListPaging' => $MailSystemListPaging,
                'MailSystemCount' => $SystemNotifyCount
            ]);
    }
}