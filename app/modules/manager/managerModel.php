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

class managerModel extends Model
{
    public function GetTotalUsers()
    {
        return db('users')->count();
    }

    public function GetTotalPosts()
    {
        return db('articles_post')->count();
    }

    public function GetTotalCategories()
    {
        return db('articles_category')->count();
    }

    public function GetTotalShouts()
    {
        return db('chat')->count();
    }
    
    // Bắt đầu File Management
    public function GetTotalFiles()
    {
        return db('articles_file')->count();
    }

    public function GetFilesList($start, $limit)
    {
        return db('articles_file')
            ->selectRaw('articles_file.*, articles_post.slug AS post_slug, users.name AS user_name, users.nick AS user_nick, users.level AS user_level, users.reg AS user_reg')
            ->leftJoin('articles_post', 'articles_file.blogid', '=', 'articles_post.id')
            ->leftJoinRaw('users ON (articles_file.author = users.id OR articles_file.author = users.nick)')
            ->orderBy('articles_file.id', 'DESC')
            ->limit((int)$limit)
            ->offset((int)$start)
            ->get();
    }

    public function DeleteFile($id)
    {
        return db('articles_file')->where('id', (int)$id)->delete() > 0;
    }
    // Kết thúc File Management
    
    public function GetUsers($start, $limit, $search = '', $sort_by = 'id')
    {
        $query = db('users')->select(['id', 'nick', 'name', 'level', 'xu', 'avatar', 'cover']);

        if (!empty($search)) {
            $query->where('id', $search)->orWhere('nick', 'LIKE', "%$search%");
        }

        if ($sort_by === 'level') {
            $query->orderBy('level', 'DESC')->orderBy('id', 'DESC');
        } elseif ($sort_by === 'status') {
            $query->orderBy('level', 'ASC')->orderBy('id', 'DESC');
        } elseif ($sort_by === 'xu') {
            $query->orderBy('xu', 'DESC')->orderBy('id', 'DESC');
        } else {
            $query->orderBy('id', 'DESC');
        }

        return $query->limit((int)$limit)->offset((int)$start)->get();
    }
    
    public function GetTotalUsersSearch($search = '')
    {
        if (!empty($search)) {
            return db('users')->where('id', $search)->orWhere('nick', 'LIKE', "%$search%")->count();
        }
        return $this->GetTotalUsers();
    }
    
    public function ToggleBanUser($id, $admin_id = 0)
    {
        $id = (int)$id;
        $admin_id = (int)$admin_id;
        if ($admin_id > 0 && $id === $admin_id) {
            return false;
        }

        $user = db('users')->select(['id', 'level'])->where('id', $id)->first();
        if ($user) {
            if ($admin_id > 0) {
                $admin = db('users')->select(['level'])->where('id', $admin_id)->first();
                if (!$admin || $user['level'] >= $admin['level']) {
                    return false;
                }
            }
            $new_level = ($user['level'] >= 0) ? -1 : 0;

            db('users')->where('id', $id)->update(['level' => $new_level]);
            return true;
        }
        return false;
    }

    public function ResetPassword($target_id, $admin_id, $admin_pass_input)
    {
        $target_id = (int)$target_id;
        $admin_id = (int)$admin_id;
        
        // Kiểm tra admin
        $admin = db('users')->select(['id', 'pass', 'level'])->where('id', $admin_id)->first();
        if (!$admin || !verify_password_seamless($admin_pass_input, $admin['pass'], $admin['id'], $this->db)) {
            return ['status' => false, 'message' => 'Mật khẩu xác nhận không chính xác.'];
        }

        // Kiểm tra user bị reset
        $target = db('users')->select(['id', 'nick', 'level'])->where('id', $target_id)->first();
        if (!$target) {
            return ['status' => false, 'message' => 'Người dùng không tồn tại.'];
        }

        // Không cho phép reset người có level cao hơn hoặc bằng
        if ($target['level'] >= $admin['level']) {
            return ['status' => false, 'message' => 'Không thể reset mật khẩu của thành viên có cấp độ cao hơn hoặc bằng bạn.'];
        }

        // Mật khẩu mới: nick + 123000
        $new_pass_plain = strtolower($target['nick']) . '123000';
        $new_pass_hash = hash_password_secure($new_pass_plain);

        db('users')->where('id', $target_id)->update([
            'pass'          => $new_pass_hash,
            'failed_logins' => 0,
            'lock_time'     => 0,
        ]);

        return ['status' => true, 'message' => 'Đã reset mật khẩu thành công về: ' . $new_pass_plain];
    }

    public function EditUser($target_id, $admin_id, $data)
    {
        $target_id = (int)$target_id;
        $admin_id = (int)$admin_id;

        $admin = db('users')->select(['level'])->where('id', $admin_id)->first();
        $target = db('users')->select(['level', 'nick'])->where('id', $target_id)->first();

        if (!$admin || !$target) {
            return ['status' => false, 'message' => 'Tài khoản không tồn tại.'];
        }

        // Quyền chỉnh sửa: Chỉ tự sửa mình hoặc người có level bé hơn mình
        if ($target_id != $admin_id && $target['level'] >= $admin['level']) {
            return ['status' => false, 'message' => 'Không thể sửa thành viên có level cao hơn hoặc bằng bạn.'];
        }

        // Nếu tự sửa mình thì không được sửa level
        if ($target_id == $admin_id) {
            $data['level'] = $admin['level'];
        } else {
            if ($admin['level'] <= 122) {
                $data['level'] = $target['level'];
            } elseif ($data['level'] >= $admin['level'] && $admin['level'] < 127) {
                return ['status' => false, 'message' => 'Không thể set level cho người khác cao hơn hoặc bằng level của chính bạn.'];
            }
        }

        db('users')->where('id', $target_id)->update([
            'name'  => $data['name'],
            'level' => $data['level'],
            'xu'    => $data['xu'],
        ]);

        return ['status' => true, 'message' => 'Đã cập nhật thông tin thành viên ' . $target['nick'] . ' thành công!'];
    }

    public function CleanShoutbox()
    {
        // Lấy 50 tin nhắn mới nhất
        $messages = db('chat')->orderBy('id', 'DESC')->limit(50)->get();
        $messages = array_reverse($messages);

        // Xóa toàn bộ dữ liệu trong bảng chat
        db('chat')->delete();

        // Insert lại 50 tin nhắn
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                db('chat')->insert([
                    'name'    => $msg['name'],
                    'time'    => $msg['time'],
                    'comment' => $msg['comment'],
                ]);
            }
        }
        
        return true;
    }

    public function GetArticlesCategories()
    {
        return db('articles_category')->orderBy('id', 'ASC')->get();
    }

    public function AddArticlesCategory($data)
    {
        return db('articles_category')->insert([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'keyword' => $data['keyword'],
            'meta_data' => $data['meta_data'] ?? null
        ]) > 0;
    }

    public function EditArticlesCategory($id, $data)
    {
        return db('articles_category')->where('id', $id)->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'keyword' => $data['keyword'],
            'meta_data' => $data['meta_data'] ?? null,
        ]) >= 0;
    }

    public function DeleteArticlesCategory($id)
    {
        // Require articlesModel for deep delete (posts, comments, files)
        require_once __DIR__ . '/../articles/articlesModel.php';
        $articlesModel = new \articlesModel();
        $articlesModel->CategoryDelete($id);
        return true;
    }

    public function GetArticlesPosts($start = 0, $limit = 20, $search = '')
    {
        $query = db('articles_post')
            ->selectRaw('articles_post.id, articles_post.title, articles_post.slug, articles_post.author, articles_post.time, articles_post.view, articles_category.name AS category_name')
            ->leftJoin('articles_category', 'articles_post.category', '=', 'articles_category.id');

        if (!empty($search)) {
            $query->where('articles_post.id', $search)->orWhere('articles_post.title', 'LIKE', "%$search%");
        }

        return $query
            ->orderBy('articles_post.id', 'DESC')
            ->limit((int)$limit)
            ->offset((int)$start)
            ->get();
    }

    public function GetTotalArticlesPosts($search = '')
    {
        if (!empty($search)) {
            return db('articles_post')->where('id', $search)->orWhere('title', 'LIKE', "%$search%")->count();
        }
        return db('articles_post')->count();
    }

    public function DeleteArticlesPost($id)
    {
        $id = (int)$id;
        db('articles_post')->where('id', $id)->delete();
        db('articles_cmt')->where('blogid', $id)->delete();
        db('articles_chap')->where('box', $id)->delete();
        db('articles_file')->where('blogid', $id)->delete();

        require_once __DIR__ . '/../articles/articlesModel.php';
        $articlesModel = new \articlesModel();
        $articlesModel->TagsDeleteByPost($id);

        return true;
    }

    public function GetArticlesChapters($start = 0, $limit = 20, $search = '')
    {
        $query = db('articles_chap')
            ->selectRaw('articles_chap.id, articles_chap.title, articles_chap.slug, articles_chap.author, articles_chap.time, articles_chap.box, articles_post.title AS post_title, articles_post.slug AS post_slug')
            ->leftJoin('articles_post', 'articles_chap.box', '=', 'articles_post.id');

        if (!empty($search)) {
            $query->where('articles_chap.id', $search)->orWhere('articles_chap.title', 'LIKE', "%$search%");
        }

        return $query
            ->orderBy('articles_chap.id', 'DESC')
            ->limit((int)$limit)
            ->offset((int)$start)
            ->get();
    }

    public function GetTotalArticlesChapters($search = '')
    {
        if (!empty($search)) {
            return db('articles_chap')->where('id', $search)->orWhere('title', 'LIKE', "%$search%")->count();
        }
        return db('articles_chap')->count();
    }

    public function DeleteArticlesChapter($id)
    {
        db('articles_chap')->where('id', (int)$id)->delete();
        return true;
    }
}
