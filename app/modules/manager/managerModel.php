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
        return $this->db->query("SELECT COUNT(id) FROM users")->fetchColumn();
    }

    public function GetTotalPosts()
    {
        return $this->db->query("SELECT COUNT(id) FROM articles_post")->fetchColumn();
    }

    public function GetTotalCategories()
    {
        return $this->db->query("SELECT COUNT(id) FROM articles_category")->fetchColumn();
    }

    public function GetTotalShouts()
    {
        return $this->db->query("SELECT COUNT(id) FROM chat")->fetchColumn();
    }
    
    // Bắt đầu File Management
    public function GetTotalFiles()
    {
        return $this->db->query("SELECT COUNT(id) FROM articles_file")->fetchColumn();
    }

    public function GetFilesList($start, $limit)
    {
        $start = (int)$start;
        $limit = (int)$limit;
        $stmt = $this->db->prepare("
            SELECT f.*, p.slug as post_slug, u.name as user_name, u.nick as user_nick, u.level as user_level, u.reg as user_reg
            FROM articles_file f
            LEFT JOIN articles_post p ON f.blogid = p.id
            LEFT JOIN users u ON (f.author = u.id OR f.author = u.nick)
            ORDER BY f.id DESC 
            LIMIT :start, :limit
        ");
        $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function DeleteFile($id)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("DELETE FROM articles_file WHERE id = ?");
        return $stmt->execute([$id]);
    }
    // Kết thúc File Management
    
    public function GetUsers($start, $limit, $search = '', $sort_by = 'id')
    {
        $start = (int)$start;
        $limit = (int)$limit;
        
        $order_sql = "ORDER BY id DESC";
        if ($sort_by === 'level') $order_sql = "ORDER BY level DESC, id DESC";
        elseif ($sort_by === 'status') $order_sql = "ORDER BY level ASC, id DESC"; // Banned users (level < 0) will be at top
        elseif ($sort_by === 'xu') $order_sql = "ORDER BY xu DESC, id DESC";

        if (!empty($search)) {
            $stmt = $this->db->prepare("SELECT id, nick, name, level, xu, avatar, cover FROM users WHERE id = :search OR nick LIKE :search_like $order_sql LIMIT :start, :limit");
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':search_like', "%$search%");
            $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        
        $stmt = $this->db->prepare("SELECT id, nick, name, level, xu, avatar, cover FROM users $order_sql LIMIT :start, :limit");
        $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function GetTotalUsersSearch($search = '')
    {
        if (!empty($search)) {
            $stmt = $this->db->prepare("SELECT COUNT(id) FROM users WHERE id = :search OR nick LIKE :search_like");
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':search_like', "%$search%");
            $stmt->execute();
            return $stmt->fetchColumn();
        }
        return $this->GetTotalUsers();
    }
    
    public function ToggleBanUser($id)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("SELECT id, level FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $new_level = ($user['level'] >= 0) ? -1 : 0;
            
            $stmt = $this->db->prepare("UPDATE users SET level = :level WHERE id = :id");
            $stmt->execute([
                ':level' => $new_level,
                ':id' => $id
            ]);
            return true;
        }
        return false;
    }

    public function ResetPassword($target_id, $admin_id, $admin_pass_input)
    {
        $target_id = (int)$target_id;
        $admin_id = (int)$admin_id;
        
        // Kiểm tra admin
        $stmt = $this->db->prepare("SELECT id, password, level FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        if (!$admin || !password_verify($admin_pass_input, $admin['password'])) {
            return ['status' => false, 'message' => 'Mật khẩu xác nhận không chính xác.'];
        }

        // Kiểm tra user bị reset
        $stmt = $this->db->prepare("SELECT id, nick, level FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $target = $stmt->fetch();
        if (!$target) {
            return ['status' => false, 'message' => 'Người dùng không tồn tại.'];
        }

        // Không cho phép reset người có level cao hơn hoặc bằng
        if ($target['level'] >= $admin['level']) {
            return ['status' => false, 'message' => 'Không thể reset mật khẩu của thành viên có cấp độ cao hơn hoặc bằng bạn.'];
        }

        // Mật khẩu mới: nick + 123000
        $new_pass_plain = strtolower($target['nick']) . '123000';
        $new_pass_hash = password_hash($new_pass_plain, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute([
            ':password' => $new_pass_hash,
            ':id' => $target_id
        ]);

        return ['status' => true, 'message' => 'Đã reset mật khẩu thành công về: ' . $new_pass_plain];
    }

    public function EditUser($target_id, $admin_id, $data)
    {
        $target_id = (int)$target_id;
        $admin_id = (int)$admin_id;

        $stmt_admin = $this->db->prepare("SELECT level FROM users WHERE id = ?");
        $stmt_admin->execute([$admin_id]);
        $admin = $stmt_admin->fetch();
        
        $stmt_target = $this->db->prepare("SELECT level, nick FROM users WHERE id = ?");
        $stmt_target->execute([$target_id]);
        $target = $stmt_target->fetch();

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

        $stmt = $this->db->prepare("UPDATE users SET name = :name, level = :level, xu = :xu WHERE id = :id");
        $stmt->execute([
            ':name' => $data['name'],
            ':level' => $data['level'],
            ':xu' => $data['xu'],
            ':id' => $target_id
        ]);

        return ['status' => true, 'message' => 'Đã cập nhật thông tin thành viên ' . $target['nick'] . ' thành công!'];
    }

    public function CleanShoutbox()
    {
        // Lấy 50 tin nhắn mới nhất
        $messages = $this->db->query("SELECT * FROM chat ORDER BY id DESC LIMIT 50")->fetchAll(\PDO::FETCH_ASSOC);
        $messages = array_reverse($messages); // Đảo ngược để lưu theo đúng thứ tự thời gian

        // Xóa toàn bộ dữ liệu trong bảng chat và reset auto_increment về 1
        $this->db->query("TRUNCATE TABLE chat");

        // Insert lại 50 tin nhắn với ID mới tự tăng (từ 1 đến tối đa 50)
        if (!empty($messages)) {
            $stmt = $this->db->prepare("INSERT INTO chat (name, time, comment) VALUES (:name, :time, :comment)");
            foreach ($messages as $msg) {
                $stmt->execute([
                    'name' => $msg['name'],
                    'time' => $msg['time'],
                    'comment' => $msg['comment']
                ]);
            }
        }
        
        return true;
    }

    public function GetArticlesCategories()
    {
        return $this->db->query("SELECT * FROM articles_category ORDER BY id ASC")->fetchAll();
    }

    public function AddArticlesCategory($data)
    {
        $stmt = $this->db->prepare("INSERT INTO articles_category (name, slug, content, keyword, meta_data) VALUES (:name, :slug, :content, :keyword, :meta_data)");
        return $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'keyword' => $data['keyword'],
            'meta_data' => $data['meta_data'] ?? null
        ]);
    }

    public function EditArticlesCategory($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE articles_category SET name = :name, slug = :slug, content = :content, keyword = :keyword, meta_data = :meta_data WHERE id = :id");
        return $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'content' => $data['content'],
            'keyword' => $data['keyword'],
            'meta_data' => $data['meta_data'] ?? null,
            'id' => $id
        ]);
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
        $start = (int)$start;
        $limit = (int)$limit;
        
        $sql = "SELECT p.id, p.title, p.slug, p.author, p.time, p.view, c.name as category_name 
                FROM articles_post p 
                LEFT JOIN articles_category c ON p.category = c.id";
        
        if (!empty($search)) {
            $sql .= " WHERE p.id = :search OR p.title LIKE :search_like";
            $sql .= " ORDER BY p.id DESC LIMIT :start, :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':search_like', "%$search%");
            $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        
        $sql .= " ORDER BY p.id DESC LIMIT :start, :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function GetTotalArticlesPosts($search = '')
    {
        if (!empty($search)) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles_post WHERE id = :search OR title LIKE :search_like");
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':search_like', "%$search%");
            $stmt->execute();
            return $stmt->fetchColumn();
        }
        return $this->db->query("SELECT COUNT(*) FROM articles_post")->fetchColumn();
    }

    public function DeleteArticlesPost($id)
    {
        $id = (int)$id;
        $stmt1 = $this->db->prepare("DELETE FROM articles_post WHERE id = ?");
        $stmt1->execute([$id]);
        $stmt2 = $this->db->prepare("DELETE FROM articles_cmt WHERE blogid = ?");
        $stmt2->execute([$id]);
        $stmt3 = $this->db->prepare("DELETE FROM articles_chap WHERE box = ?");
        $stmt3->execute([$id]);
        $stmt4 = $this->db->prepare("DELETE FROM articles_file WHERE blogid = ?");
        $stmt4->execute([$id]);
        return true;
    }

    public function GetArticlesChapters($start = 0, $limit = 20, $search = '')
    {
        $start = (int)$start;
        $limit = (int)$limit;
        
        $sql = "SELECT c.id, c.title, c.slug, c.author, c.time, c.box, p.title as post_title 
                FROM articles_chap c 
                LEFT JOIN articles_post p ON c.box = p.id";
        
        if (!empty($search)) {
            $sql .= " WHERE c.id = :search OR c.title LIKE :search_like";
            $sql .= " ORDER BY c.id DESC LIMIT :start, :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':search_like', "%$search%");
            $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }
        
        $sql .= " ORDER BY c.id DESC LIMIT :start, :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start', (int)$start, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function GetTotalArticlesChapters($search = '')
    {
        if (!empty($search)) {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles_chap WHERE id = :search OR title LIKE :search_like");
            $stmt->bindValue(':search', $search);
            $stmt->bindValue(':search_like', "%$search%");
            $stmt->execute();
            return $stmt->fetchColumn();
        }
        return $this->db->query("SELECT COUNT(*) FROM articles_chap")->fetchColumn();
    }

    public function DeleteArticlesChapter($id)
    {
        $id = (int)$id;
        $stmt1 = $this->db->prepare("DELETE FROM articles_chap WHERE id = ?");
        $stmt1->execute([$id]);
        
        $stmt2 = $this->db->prepare("DELETE FROM articles_cmt WHERE chap = ?");
        $stmt2->execute([$id]);
        return true;
    }
}
