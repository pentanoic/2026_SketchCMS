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

class articlesModel extends Model
{
    /*
    //private userModel $userModel;

    function __construct()
    {
        //parent::__construct();
        //$this->userModel = $this->load->model('user');
    }
    */

    private $prefix = 'articles_';
    private $PostTable;
    private $CategoryTable;
    private $CommentTable;
    private $ChapterTable;
    private $FileTable;

    public function __construct()
    {
        parent::__construct();
        $this->PostTable = $this->prefix . 'post';
        $this->CategoryTable = $this->prefix . 'category';
        $this->CommentTable = $this->prefix . 'cmt';
        $this->ChapterTable = $this->prefix . 'chap';
        $this->FileTable = $this->prefix . 'file';
        $this->TagTable = $this->prefix . 'tags';
        $this->PostTagTable = $this->prefix . 'post_tags';
    }

    public function CountTotalPostsAndComments($authorId)
    {
        $stmt1 = $this->db->prepare("SELECT COUNT(*) FROM $this->PostTable WHERE author = :author");
        $stmt1->execute(['author' => $authorId]);
        $posts = $stmt1->fetchColumn();

        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM $this->CommentTable WHERE author = :author");
        $stmt2->execute(['author' => $authorId]);
        $comments = $stmt2->fetchColumn();

        return $posts + $comments;
    }

    //===== PHẦN CHUNG =====//
    public function ForumSearch($query)
    {
        /**
         * tìm kiếm trong bảng `blog` -  trường `title`; bảng `chap` - trường `title`
         * trả về, nếu dữ liệu thuộc bảng blog:
         * ** ['url' => '/articles/id-slug.html, 'title', 'author', 'time']
         * nếu dữ liệu thuộc bảng chap:
         * ** ['url' => '/view-chap/id-slug.html, 'title', 'author', 'time']
         */
        $blogQuery = "
            SELECT '$this->PostTable' AS source, id, title, author, time, CONCAT('/articles/', id, '-', slug, '.html') AS url
            FROM $this->PostTable
            WHERE title LIKE :query
        ";
        $chapQuery = "
            SELECT '$this->ChapterTable' AS source, id, title, author, time, CONCAT('/view-chap/', id, '-', slug, '.html') AS url
            FROM $this->ChapterTable
            WHERE title LIKE :query
        ";
        $combinedQuery = "
            ($blogQuery)
            UNION ALL
            ($chapQuery)
            ORDER BY time DESC
        ";

        $stmt = $this->db->prepare($combinedQuery);
        $stmt->execute(['query' => '%' . $query . '%']);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'count' => count($results),
            'result' => $results
        ];
    }

    public function ForumDetailWithFields($table = null, $fields = null, $value = null)
    {
        if (!$table || !$fields || !$value) {
            return null;
            exit();
        }

        $output = [];
        $stmt = $this->db->prepare('SELECT * FROM ' . $table . ' WHERE ' . $fields . ' = :value LIMIT 1');
        $stmt->execute(['value' => $value]);
        $output = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $output;
    }

    public function ForumCheckExists($table = null, $fields = null, $value = null)
    {
        if (!$table || !$fields || !$value) {
            return null;
            exit();
        }

        $stmt = $this->db->prepare("
            SELECT EXISTS(
                SELECT 1 FROM $table WHERE $fields = :value
            ) AS exists_flag
        ");
        $stmt->bindParam(':value', $value, \PDO::PARAM_INT);
        $stmt->execute();
        $exists = $stmt->fetchColumn(); // lấy giá trị exists_flag (0 hoặc 1)
        return (bool) $exists;
    }

    public function ForumStats($table = null, $id = null)
    {
        $counts = [];
        $stats = null;
        if (
            !in_array($table, [
                'count_post_in_category',
                'count_comment_in_post',
                'count_chapter_in_post',
                'count_file_in_post'
            ])
        ) { // thống kê cục bộ
            # Đếm số hàng trong các bảng
            $stmtCount = $this->db->prepare("
                SELECT
                    (SELECT COUNT(*) FROM $this->CategoryTable WHERE slug != 'news') AS count_category,
                    (SELECT COUNT(*) FROM $this->PostTable) AS count_post,
                    (SELECT COUNT(*) FROM $this->ChapterTable) AS count_chapter,
                    (SELECT COUNT(*) FROM $this->CommentTable) AS count_comment,
                    (SELECT COUNT(*) FROM $this->FileTable) AS count_file,
                    (SELECT COUNT(*) FROM users WHERE level >= 0) AS count_user,
                    (SELECT COUNT(*) FROM chat) AS count_chat,
                    (SELECT COUNT(*) FROM users WHERE `on` > :onlimit) AS count_online
            ");
            $stmtCount->execute(['onlimit' => (date('U') - 300)]);
            $counts = $stmtCount->fetch(\PDO::FETCH_ASSOC);
            # Lấy dữ liệu hàng cuối cùng từ bảng users
            $stmtLatestUser = $this->db->prepare('SELECT * FROM users WHERE level >= 0 ORDER BY id DESC LIMIT 1');
            $stmtLatestUser->execute();
            $latestUser = $stmtLatestUser->fetch(\PDO::FETCH_ASSOC);
            # Kết hợp kết quả vào một mảng duy nhất$
            $stats = array_merge($counts, ['latest_user' => $latestUser]);
        } else { // đếm bài viết trong category, đếm comment và chaptet trong bài viết
            if ($table === 'count_post_in_category') {
                $sql = "SELECT COUNT(*) AS count FROM $this->PostTable WHERE `category` = :id";
            } elseif ($table === 'count_comment_in_post') {
                $sql = "SELECT COUNT(*) AS count FROM $this->CommentTable WHERE `blogid` = :id";
            } elseif ($table === 'count_file_in_post') {
                $sql = "SELECT COUNT(*) AS count FROM $this->FileTable WHERE `blogid` = :id";
            } else {
                $sql = "SELECT COUNT(*) AS count FROM $this->ChapterTable WHERE `box` = :id";
            }

            $stmtCount = $this->db->prepare($sql);
            $stmtCount->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmtCount->execute();
            $counts = $stmtCount->fetch(\PDO::FETCH_ASSOC);
            $stats = $counts['count'];
        }

        return $stats;
    }

    //===== DÀNH CHO CHUYÊN MỤC =====//
    public function CategoryList($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT c.*, COUNT(b.id) AS count_post
            FROM $this->CategoryTable c
            LEFT JOIN $this->PostTable b ON c.id = b.category
            WHERE c.slug != 'news'
            GROUP BY c.id
            ORDER BY c.id ASC
            LIMIT :limit
        ");
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll();
        return $data;
    }

    public function CategoryExist($category_id)
    {
        return $this->ForumCheckExists($this->CategoryTable, 'id', $category_id);
    }

    public function CategoryDetail($category_id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM $this->CategoryTable WHERE id = :category_id LIMIT 1
        ");
        $stmt->bindParam(':category_id', $category_id, \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ?: null; // nếu không có thì trả về null
    }

    public function CategoryPublish($name, $slug, $content, $keyword)
    {
        $stmt = $this->db->prepare("INSERT INTO `$this->CategoryTable` SET
            `name` = :name,
            `slug` = :slug,
            `content` = :content,
            `keyword` = :keyword
        ");
        $stmt->execute([
            'name' => _e($name),
            'slug' => _e($slug),
            'content' => _e($content),
            'keyword' => _e($keyword),
        ]);

        return $this->db->lastInsertId();
    }

    public function CategoryDelete($category_id)
    {
        // xóa chuyên mục theo id
        $delete_category = $this->db->prepare("DELETE FROM `$this->CategoryTable` WHERE `id` = :id");
        $delete_category->execute(['id' => $category_id]);
        // lấy danh sách bài viết theo category
        $PostCount = $this->ForumStats('count_post_in_category', $category_id);
        $PostList = $this->PostList($category_id, $PostCount);
        foreach ($PostList as $PostDetail) {
            // xóa bài viết theo id
            $delete_post = $this->db->prepare("DELETE FROM `$this->PostTable` WHERE `id` = :id");
            $delete_post->execute(['id' => $PostDetail['id']]);
            // xóa comment theo id bài viết
            $delete_comment = $this->db->prepare("DELETE FROM `$this->CommentTable` WHERE `blogid` = :id");
            $delete_comment->execute(['id' => $PostDetail['id']]);
            // xóa chap theo id bài viết
            $delete_chap = $this->db->prepare("DELETE FROM `$this->ChapterTable` WHERE `box` = :id");
            $delete_chap->execute(['id' => $PostDetail['id']]);
            // xóa file theo id bài viết
            $delete_file = $this->db->prepare("DELETE FROM `$this->FileTable` WHERE `blogid` = :id");
            $delete_file->execute(['id' => $PostDetail['id']]);
            // xóa tags
            $this->TagsDeleteByPost($PostDetail['id']);
        }
        return null;
    }

    //===== DÀNH CHO BÀI VIẾT =====//
    public function PostListSticked($limit = 10)
    {
        $sql = "SELECT 
                    b.*, 
                    c.id AS category_id,
                    c.name AS category_name,
                    cm.author AS last_comment_author
                FROM $this->PostTable b
                LEFT JOIN $this->CategoryTable c ON b.category = c.id
                LEFT JOIN (
                    SELECT blogid, author
                    FROM $this->CommentTable
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM $this->CommentTable
                        GROUP BY blogid
                    )
                ) cm ON cm.blogid = b.id
                WHERE b.sticked = 1
                ORDER BY b.update_time DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function PostList($category_id = 0, $per = 10, $order_by = 'update_time', $sort = 'desc', $start = 0)
    {
        $CategoryExist = false;
        if ($category_id > 0 && $this->CategoryExist($category_id)) {
            $CategoryExist = true;
        }
        // SQL query to fetch blog posts and category details if available
        $sql = "SELECT b.*, 
                       (CASE WHEN c.id IS NOT NULL THEN c.id ELSE NULL END) AS category_id,
                       (CASE WHEN c.id IS NOT NULL THEN c.name ELSE NULL END) AS category_name
                FROM $this->PostTable b
                LEFT JOIN $this->CategoryTable c ON b.category = c.id";

        // Append conditions based on category_id
        if ($CategoryExist) {
            $sql .= ' WHERE c.id = :category_id';
        }

        if (!in_array($order_by, config('system.PostList.order_by'))) {
            $order_by = 'update_time';
        }
        if (!in_array($sort, config('system.PostList.sort'))) {
            $sort = 'desc';
        }

        // Append order and limit clauses
        $sql .= ' ORDER BY ' . $order_by . ' ' . $sort . ' 
                  LIMIT :start, :per';

        // Prepare the SQL statement
        $stmt = $this->db->prepare($sql);

        // Bind parameters
        if ($CategoryExist) {
            $stmt->bindParam(':category_id', $category_id, \PDO::PARAM_INT);
        }
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':per', $per, \PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Fetch all results
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function PostListSimilar($category_id, $post_id)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM $this->PostTable WHERE category = :category_id AND id!= :post_id ORDER BY RAND() LIMIT 5
        ");
        $stmt->bindParam(':category_id', $category_id, \PDO::PARAM_INT);
        $stmt->bindParam(':post_id', $post_id, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function PostListUser($nick, $start = 0, $end = 10)
    {
        $stmt = $this->db->prepare("
            SELECT b.*, 
                       (CASE WHEN c.id IS NOT NULL THEN c.id ELSE NULL END) AS category_id,
                       (CASE WHEN c.id IS NOT NULL THEN c.name ELSE NULL END) AS category_name
                FROM $this->PostTable b
                LEFT JOIN $this->CategoryTable c ON b.category = c.id
                WHERE author = :nick 
                ORDER BY update_time 
                DESC LIMIT :start, :end
        ");
        $stmt->bindParam(':nick', $nick, \PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':end', $end, \PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function PostCountUser($nick)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM $this->PostTable WHERE author = :nick
        ");
        $stmt->bindParam(':nick', $nick, \PDO::PARAM_STR);
        $stmt->execute();
        $counts = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats = $counts['count'];
        return $stats;
    }

    public function PostDetail($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM $this->PostTable WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $output = $stmt->fetchAll()[0];
        return $output;
    }

    public function PostLikeList($id, $action = null)
    {
        $UserModel = $this->load->model('user');
        $PostDetail = $this->PostDetail($id);
        $PostLikeList = $PostDetail['like'] ? array_filter(explode('.', $PostDetail['like'])) : [];
        $PostLikeCount = count($PostLikeList);
        $output = [];
        if ($PostLikeCount > 0) {
            foreach ($PostLikeList as $nick) {
                $UserDetail = $UserModel->UserDetailWithFields('nick', $nick);
                $output[] = $UserDetail;
            }
        }
        if ($action == 'count') {
            return $PostLikeCount;
        } else {
            return $output;
        }
    }
    public function PostLikeSave($id, $nick, $action)
    {
        $result = false;
        $nick = mb_strtolower($nick);
        $PostDetail = $this->PostDetail($id);
        $PostLikeList = $PostDetail['like'] ? explode('.', $PostDetail['like']) : [];
        $PostLikeListSave = $nick . '.' . $PostDetail['like'];
        if ($action == 'save') {
            if (!in_array($nick, $PostLikeList) && $nick != $PostDetail['author']) {
                // lưu vào bảng `blog`
                $stmt_post = $this->db->prepare("
                UPDATE `$this->PostTable` SET `like` = :like 
                WHERE `id` = :id
            ");
                $stmt_post->execute(['id' => $id, 'like' => $PostLikeListSave]);
                // lưu vào thông báo của author
                $system_notify_content = '@' . $nick . ' vừa bày tỏ cảm xúc trong một bài viết có mặt bạn. [url=/articles/' . $id . '-' . $PostDetail['slug'] . '.html][XEM BÀI VIẾT][/url]';
                $stmt_system_notify = $this->db->prepare('INSERT INTO `mail` SET
                    `sender_receiver` = :sender_receiver,
                    `nick` = :nick,
                    `content` = :content,
                    `time` = :time,
                    `view` = :view
                ');
                $stmt_system_notify->execute([
                    'sender_receiver' => $PostDetail['author'] . '_' . UserBot,
                    'nick' => $nick,
                    'content' => $system_notify_content,
                    'time' => TIME,
                    'view' => 'no'
                ]);
                $result = true;
            }
        } elseif ($action == 'check') {
            if (in_array($nick, $PostLikeList) || $nick == $PostDetail['author']) {
                $result = true;
            }
        }
        return $result;
    }

    public function PostSaveOne($id, $col, $cold_val)
    {
        $stmt = $this->db->prepare("UPDATE ".$this->PostTable." SET `" . $col . "` = :col_value WHERE `id` = :id");
        $stmt->bindValue(':col_value', $cold_val, \PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function PostPublish($category, $title, $slug, $content, $author, $meta_data = null)
    {
        $stmt = $this->db->prepare("INSERT INTO `$this->PostTable` SET
            `category` = :category,
            `title` = :title,
            `slug` = :slug,
            `content` = :content,
            `author` = :author,
            `time` = :time,
            `update_time` = :update_time,
            `meta_data` = :meta_data
        ");
        $stmt->execute([
            'category' => $category,
            'title' => $title,
            'slug' => _e($slug),
            'content' => $content,
            'author' => $author,
            'time' => TIME,
            'update_time' => TIME,
            'meta_data' => $meta_data
        ]);
        return $this->db->lastInsertId();
    }

    public function PostEdit($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE `$this->PostTable` SET
            `category` = :category,
            `title` = :title,
            `slug` = :slug,
            `content` = :content,
            `meta_data` = :meta_data
        WHERE `id` = :id
        ");
        $stmt->execute([
            'category' => $data['category'],
            'title' => $data['title'],
            'slug' => _e($data['slug']),
            'content' => $data['content'],
            'meta_data' => $data['meta_data'] ?? null,
            'id' => $id
        ]);
        return $stmt->rowCount();
    }

    public function PostDelete($id)
    {
        // xóa bài viết theo id
        $delete_post = $this->db->prepare("DELETE FROM `$this->PostTable` WHERE `id` = :id");
        $delete_post->execute(['id' => $id]);
        // xóa comment theo id bài viết
        $delete_comment = $this->db->prepare("DELETE FROM `$this->CommentTable` WHERE `blogid` = :id");
        $delete_comment->execute(['id' => $id]);
        // xóa chap theo id bài viết
        $delete_chap = $this->db->prepare("DELETE FROM `$this->ChapterTable` WHERE `box` = :id");
        $delete_chap->execute(['id' => $id]);
        // xóa file theo id bài viết
        $delete_file = $this->db->prepare("DELETE FROM `$this->FileTable` WHERE `blogid` = :id");
        $delete_file->execute(['id' => $id]);
        
        // xóa tags
        $this->TagsDeleteByPost($id);
    }

    public function PostViewUpdate($PostDetail)
    {
        $PostID = $PostDetail['id'];
        if (!isset($_SESSION['post_viewed_' . $PostID])) {
            $stmt = $this->db->prepare("UPDATE `$this->PostTable` SET `view` = :view WHERE `id` = :id");
            $stmt->execute([
                'view' => $PostDetail['view'] + 1,
                'id' => $PostDetail['id']
            ]);
            $_SESSION['post_viewed_' . $PostID] = true;
        }
        return $PostDetail['view'];
    }

    //===== DÀNH CHO CHƯƠNG BÀI VIẾT =====//
    public function ChapterList($post_id)
    {
        // SQL query to fetch blog posts and category details if available
        $sql = 'SELECT * FROM ' . $this->ChapterTable;
        if ($post_id > 0) {
            $sql .= ' WHERE box = :post_id';
        }
        $sql .= ' ORDER BY id desc';
        $stmt = $this->db->prepare($sql);
        if ($post_id > 0) {
            $stmt->bindParam(':post_id', $post_id, \PDO::PARAM_INT);
        }
        // Execute the query
        $stmt->execute();
        // Fetch all results
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function ChapterEdit($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE `$this->ChapterTable` SET
            `title` = :title,
            `slug` = :slug,
            `content` = :content,
            `meta_data` = :meta_data
        WHERE `id` = :id
        ");
        $stmt->execute([
            'title' => _e($data['title']),
            'slug' => _e($data['slug']),
            'content' => _e($data['content']),
            'meta_data' => $data['meta_data'] ?? null,
            'id' => $id
        ]);
        return $stmt->rowCount();
    }

    public function ChapterDelete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM `$this->ChapterTable` WHERE `id` = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }

    public function ChapterPublish($box, $title, $slug, $content, $author, $meta_data = null)
    {
        $stmt = $this->db->prepare("INSERT INTO `$this->ChapterTable` SET
            `box` = :box,
            `title` = :title,
            `slug` = :slug,
            `content` = :content,
            `author` = :author,
            `time` = :time,
            `meta_data` = :meta_data
        ");
        $stmt->execute([
            'box' => $box,
            'title' => _e($title),
            'slug' => _e($slug),
            'content' => _e($content),
            'author' => $author,
            'time' => TIME,
            'meta_data' => $meta_data
        ]);
        return $this->db->lastInsertId();
    }

    //===== DÀNH CHO BÌNH LUẬN =====//
    public function CommentList($post_id, $start, $end)
    {
        // SQL query to fetch blog posts and category details if available
        $sql = "
            SELECT * FROM $this->CommentTable WHERE blogid = :post_id ORDER BY id asc LIMIT :start, :per
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':post_id', $post_id, \PDO::PARAM_INT);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':per', $end, \PDO::PARAM_INT);
        // Execute the query
        $stmt->execute();
        // Fetch all results
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function CommentSend($PostDetail, $author, $comment)
    {
        // lưu bình luận
        $stmt = $this->db->prepare("INSERT INTO `$this->CommentTable` SET
            `blogid` = :blogid,
            `author` = :author,
            `comment` = :comment,
            `time` = :time
        ");
        $stmt->execute([
            'blogid' => $PostDetail['id'],
            'author' => $author,
            'comment' => $comment,
            'time' => TIME
        ]);
        // cập nhật thời gian cho post
        $stmt_post = $this->db->prepare("UPDATE `$this->PostTable` SET `update_time` = :update_time WHERE `id` = :id");
        $stmt_post->execute([
            'update_time' => TIME,
            'id' => $PostDetail['id']
        ]);
        if ($author != $PostDetail['author']) {
            // lưu vào thông báo của author post
            $system_notify_content = '@' . $author . ' đã bình luận trong một bài viết của bạn. [url=/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html][XEM BÀI VIẾT] ' . $PostDetail['title'] . '[/url]';
            $stmt_system_notify = $this->db->prepare('INSERT INTO `mail` SET
                `sender_receiver` = :sender_receiver,
                `nick` = :nick,
                `content` = :content,
                `time` = :time,
                `view` = :view
        ');
            $stmt_system_notify->execute([
                'sender_receiver' => $PostDetail['author'] . '_' . UserBot,
                'nick' => $author,
                'content' => $system_notify_content,
                'time' => TIME,
                'view' => 'no'
            ]);
        }

        // notification logic cho quote
        preg_match_all('/@\[module=([a-zA-Z0-9_]+);quote=(\d+)\]/', $comment, $matches, PREG_SET_ORDER);
        $notified_users = [];
        $notified_users[] = $author;
        if ($author != $PostDetail['author']) {
            $notified_users[] = $PostDetail['author']; // ko gửi lại cho chủ bài viết vì đã gửi ở trên
        }
        foreach ($matches as $match) {
            $module = mb_strtolower($match[1]);
            $quoteId = (int)$match[2];
            $quoteAuthorNick = null;
            $quoteUrl = '';

            try {
                if ($module === 'shoutbox') {
                    $stmt_q = $this->db->prepare('SELECT `name` FROM `chat` WHERE `id` = :id');
                    $stmt_q->execute(['id' => $quoteId]);
                    if ($row = $stmt_q->fetch()) {
                        $quoteAuthorNick = $row['name'];
                        $quoteUrl = '[url=/shoutbox]trò chuyện[/url]';
                    }
                } elseif ($module === 'articles') {
                    $stmt_q = $this->db->prepare('SELECT `author`, `blogid` FROM `articles_comments` WHERE `id` = :id');
                    $stmt_q->execute(['id' => $quoteId]);
                    if ($row = $stmt_q->fetch()) {
                        $quoteAuthorNick = $row['author'];
                        $quoteUrl = '[url=/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html]bài viết ' . $PostDetail['title'] . '[/url]';
                    }
                }

                if ($quoteAuthorNick && !in_array($quoteAuthorNick, $notified_users)) {
                    $notified_users[] = $quoteAuthorNick;
                    $system_notify_content = '@' . $author . ' đã trích dẫn bình luận của bạn trong ' . $quoteUrl;
                    
                    $bot_name = defined('UserBot') ? UserBot : 'bot';
                    
                    $stmt_notify = $this->db->prepare('INSERT INTO `mail` SET
                        `sender_receiver` = :sender_receiver,
                        `nick` = :nick,
                        `content` = :content,
                        `time` = :time,
                        `view` = :view
                    ');
                    $stmt_notify->execute([
                        'sender_receiver' => $quoteAuthorNick . '_' . $bot_name,
                        'nick' => $author,
                        'content' => $system_notify_content,
                        'time' => TIME,
                        'view' => 'no'
                    ]);
                }
            } catch (Exception $e) {}
        }

        return null;
    }

    //===== PHẦN DÀNH CHO CÁC LƯU TRỮ ĐÃ CŨ =====//
    public function FileList($per, $start)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM `$this->FileTable`
            ORDER BY `id` DESC
            LIMIT :per OFFSET :start
        ");
        $stmt->bindParam(':per', $per, \PDO::PARAM_INT);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->execute();
        $output = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $output;
    }

    public function FileListForPost($PostDetail)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM `$this->FileTable`
            WHERE `blogid` = :blogid
            ORDER BY `id` DESC
        ");
        $stmt->bindParam(':blogid', $PostDetail['id'], \PDO::PARAM_INT);
        $stmt->execute();
        $output = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $output;
    }

    public function FileDetail($id)
    {
        $stmt = $this->ForumDetailWithFields($this->FileTable, 'id', $id);
        return $stmt;
    }

    public function FilePurchaserList($id)
    {
        $FileDetail = $this->FileDetail($id);
        $getBoughtList = [];
        if ($FileDetail['mua']) {
            $getBoughtList = explode('.', $FileDetail['mua']);
            $getBoughtList = array_filter($getBoughtList, function ($value) {
                return $value !== null && $value !== false && $value !== "";
            });
        }
        return $getBoughtList;
    }

    public function FileBoughtCount($id)
    {
        $FileBoughtList = $this->FilePurchaserList($id);
        $count = count($FileBoughtList);
        return $count;
    }

    public function FileBuy($FileDetail, $MyDetail)
    {
        // trừ xu của chủ đầu tư
        $stmtPurchaser = $this->db->prepare('UPDATE `users` SET `xu` = :xu WHERE `nick` = :nick');
        $stmtPurchaser->execute([
            'xu' => ($MyDetail['xu'] - $FileDetail['price']),
            'nick' => $MyDetail['nick']
        ]);
        // thêm chủ đầu tư vào danh sách đã mua
        $stmtFile = $this->db->prepare("UPDATE `$this->FileTable` SET `mua` = :mua WHERE `id` = :id");
        $stmtFile->execute([
            'id' => $FileDetail['id'],
            'mua' => ($MyDetail['nick'] . '.' . $FileDetail['mua'])
        ]);
        return 'Success';
    }

    public function FileDelete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM `$this->FileTable` WHERE `id` = :id");
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }



    // Upload Telegram
    public function FileUploadTelegram($PostDetail, $data)
    {
        $stmt = $this->db->prepare("INSERT INTO `$this->FileTable` SET
            `time` = :time,
            `filename` = :filename,
            `filecate` = :filecate,
            `filesize` = :filesize,
            `type` = :type,
            `author` = :author,
            `status` = :status,
            `price` = :price,
            `saleoff` = :saleoff,
            `condition` = :condition,
            `blogid` = :blogid
        ");
        $stmt->execute([
            'time' => TIME,
            'filename' => _e($data['filename']),
            'filecate' => _e($data['filecate']),
            'filesize' => _e($data['filesize']),
            'type' => 'telegram',
            'author' => $PostDetail['author'],
            'status' => _e($data['status']),
            'price' => $data['price'],
            'saleoff' => $data['saleoff'],
            'condition' => $data['condition'],
            'blogid' => $PostDetail['id']
        ]);
        return $this->db->lastInsertId();
    }

    //===== DÀNH CHO TAGS =====//
    public function TagGetBySlug($slug)
    {
        $stmt = $this->db->prepare("SELECT * FROM `$this->TagTable` WHERE `slug` = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function TagsGetByPost($post_id)
    {
        $sql = "SELECT t.* FROM `$this->TagTable` t 
                JOIN `$this->PostTagTable` pt ON t.id = pt.tag_id 
                WHERE pt.post_id = :post_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['post_id' => $post_id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function PostListByTag($tag_id, $per = 10, $start = 0)
    {
        $sql = "SELECT b.*, 
                       (CASE WHEN c.id IS NOT NULL THEN c.id ELSE NULL END) AS category_id,
                       (CASE WHEN c.id IS NOT NULL THEN c.name ELSE NULL END) AS category_name
                FROM `$this->PostTable` b
                LEFT JOIN `$this->CategoryTable` c ON b.category = c.id
                JOIN `$this->PostTagTable` pt ON b.id = pt.post_id
                WHERE pt.tag_id = :tag_id
                ORDER BY b.update_time DESC
                LIMIT :start, :per";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tag_id', $tag_id, \PDO::PARAM_INT);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':per', $per, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function ForumStatsByTag($tag_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `$this->PostTagTable` WHERE `tag_id` = :tag_id");
        $stmt->execute(['tag_id' => $tag_id]);
        return $stmt->fetchColumn();
    }

    public function TagsUpdateForPost($post_id, $tags_array)
    {
        // 1. Get current tags for the post
        $current_tags_rows = $this->TagsGetByPost($post_id);
        $current_tag_ids = [];
        foreach ($current_tags_rows as $row) {
            $current_tag_ids[] = $row['id'];
        }

        $new_tag_ids = [];
        foreach ($tags_array as $tag) {
            $name = $tag['name'];
            $slug = $tag['slug'];

            // Check if tag exists
            $stmt = $this->db->prepare("SELECT `id` FROM `$this->TagTable` WHERE `slug` = :slug LIMIT 1");
            $stmt->execute(['slug' => $slug]);
            $tag_row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($tag_row) {
                $tag_id = $tag_row['id'];
            } else {
                // Create new tag
                $stmt = $this->db->prepare("INSERT INTO `$this->TagTable` (`name`, `slug`, `count`) VALUES (:name, :slug, 0)");
                $stmt->execute(['name' => $name, 'slug' => $slug]);
                $tag_id = $this->db->lastInsertId();
            }
            $new_tag_ids[] = $tag_id;
        }

        // 2. Identify tags to add and remove
        $tags_to_add = array_diff($new_tag_ids, $current_tag_ids);
        $tags_to_remove = array_diff($current_tag_ids, $new_tag_ids);

        // 3. Add new tag relations
        foreach ($tags_to_add as $tag_id) {
            $stmt = $this->db->prepare("INSERT IGNORE INTO `$this->PostTagTable` (`post_id`, `tag_id`) VALUES (:post_id, :tag_id)");
            $stmt->execute(['post_id' => $post_id, 'tag_id' => $tag_id]);
            
            $this->db->prepare("UPDATE `$this->TagTable` SET `count` = `count` + 1 WHERE `id` = :tag_id")->execute(['tag_id' => $tag_id]);
        }

        // 4. Remove old tag relations
        foreach ($tags_to_remove as $tag_id) {
            $stmt = $this->db->prepare("DELETE FROM `$this->PostTagTable` WHERE `post_id` = :post_id AND `tag_id` = :tag_id");
            $stmt->execute(['post_id' => $post_id, 'tag_id' => $tag_id]);
            
            $this->db->prepare("UPDATE `$this->TagTable` SET `count` = GREATEST(0, `count` - 1) WHERE `id` = :tag_id")->execute(['tag_id' => $tag_id]);
        }
    }

    public function TagsDeleteByPost($post_id)
    {
        $current_tags_rows = $this->TagsGetByPost($post_id);
        foreach ($current_tags_rows as $row) {
            $tag_id = $row['id'];
            // Decrement count
            $this->db->prepare("UPDATE `$this->TagTable` SET `count` = GREATEST(0, `count` - 1) WHERE `id` = :tag_id")->execute(['tag_id' => $tag_id]);
        }
        $stmt = $this->db->prepare("DELETE FROM `$this->PostTagTable` WHERE `post_id` = :post_id");
        $stmt->execute(['post_id' => $post_id]);
    }
}