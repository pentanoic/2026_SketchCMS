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
        $posts = db($this->PostTable)->where('author', $authorId)->count();
        $comments = db($this->CommentTable)->where('author', $authorId)->count();
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
        $like = '%' . $query . '%';
        $posts = db($this->PostTable)
            ->select(['id', 'title', 'author', 'time', 'slug'])
            ->where('title', 'LIKE', $like)
            ->get();
        foreach ($posts as &$p) {
            $p['source'] = $this->PostTable;
            $p['url'] = '/articles/' . $p['id'] . '-' . $p['slug'] . '.html';
        }
        unset($p);

        $chaps = db($this->ChapterTable)
            ->select(['id', 'title', 'author', 'time', 'slug'])
            ->where('title', 'LIKE', $like)
            ->get();
        foreach ($chaps as &$c) {
            $c['source'] = $this->ChapterTable;
            $c['url'] = '/view-chap/' . $c['id'] . '-' . $c['slug'] . '.html';
        }
        unset($c);

        $results = array_merge($posts, $chaps);
        usort($results, static fn($a, $b) => ((int) ($b['time'] ?? 0)) <=> ((int) ($a['time'] ?? 0)));

        return [
            'count' => count($results),
            'result' => $results
        ];
    }

    public function ForumDetailWithFields($table = null, $fields = null, $value = null)
    {
        if (!$table || !$fields || $value === null || $value === '') {
            return null;
        }
        $allowedTables = [
            'articles_post', 'articles_category', 'articles_cmt', 'articles_chap',
            'articles_file', 'articles_tags', 'articles_post_tags',
            'users', 'users_waifu', 'chat', 'mail'
        ];
        if (!in_array($table, $allowedTables, true) || !preg_match('/^[a-zA-Z0-9_]+$/', (string)$fields)) {
            return null;
        }

        return db($table)->where($fields, $value)->first();
    }

    public function ForumCheckExists($table = null, $fields = null, $value = null)
    {
        if (!$table || !$fields || $value === null || $value === '') {
            return false;
        }
        $allowedTables = [
            'articles_post', 'articles_category', 'articles_cmt', 'articles_chap',
            'articles_file', 'articles_tags', 'articles_post_tags',
            'users', 'users_waifu', 'chat', 'mail'
        ];
        if (!in_array($table, $allowedTables, true) || !preg_match('/^[a-zA-Z0-9_]+$/', (string)$fields)) {
            return false;
        }

        return db($table)->where($fields, $value)->exists();
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
            $counts = [
                'count_category' => db($this->CategoryTable)->where('slug', '!=', 'news')->count(),
                'count_post'     => db($this->PostTable)->count(),
                'count_chapter'  => db($this->ChapterTable)->count(),
                'count_comment'  => db($this->CommentTable)->count(),
                'count_file'     => db($this->FileTable)->count(),
                'count_user'     => db('users')->where('level', '>=', 0)->count(),
                'count_chat'     => db('chat')->count(),
                'count_online'   => db('users')->where('on', '>', date('U') - 300)->count(),
            ];
            $latestUser = db('users')->where('level', '>=', 0)->orderBy('id', 'DESC')->first();
            $stats = array_merge($counts, ['latest_user' => $latestUser]);
        } else { // đếm bài viết trong category, đếm comment và chaptet trong bài viết
            if ($table === 'count_post_in_category') {
                $stats = db($this->PostTable)->where('category', (int)$id)->count();
            } elseif ($table === 'count_comment_in_post') {
                $stats = db($this->CommentTable)->where('blogid', (int)$id)->count();
            } elseif ($table === 'count_file_in_post') {
                $stats = db($this->FileTable)->where('blogid', (int)$id)->count();
            } else {
                $stats = db($this->ChapterTable)->where('box', (int)$id)->count();
            }
        }

        return $stats;
    }

    //===== DÀNH CHO CHUYÊN MỤC =====//
    public function CategoryList($limit = 10)
    {
        return db($this->CategoryTable)
            ->selectRaw("{$this->CategoryTable}.*, COUNT({$this->PostTable}.id) AS count_post")
            ->leftJoin($this->PostTable, "{$this->CategoryTable}.id", '=', "{$this->PostTable}.category")
            ->where("{$this->CategoryTable}.slug", '!=', 'news')
            ->groupBy("{$this->CategoryTable}.id")
            ->orderBy("{$this->CategoryTable}.id", 'ASC')
            ->limit((int)$limit)
            ->get();
    }

    public function CategoryExist($category_id)
    {
        return $this->ForumCheckExists($this->CategoryTable, 'id', $category_id);
    }

    public function CategoryDetail($category_id)
    {
        return db($this->CategoryTable)->where('id', (int)$category_id)->first();
    }

    public function CategoryPublish($name, $slug, $content, $keyword)
    {
        return db($this->CategoryTable)->insert([
            'name'    => _e($name),
            'slug'    => _e($slug),
            'content' => _e($content),
            'keyword' => _e($keyword),
        ]);
    }

    public function CategoryDelete($category_id)
    {
        db($this->CategoryTable)->where('id', (int)$category_id)->delete();
        // lấy danh sách bài viết theo category
        $PostCount = $this->ForumStats('count_post_in_category', $category_id);
        $PostList = $this->PostList($category_id, $PostCount);
        foreach ($PostList as $PostDetail) {
            db($this->PostTable)->where('id', $PostDetail['id'])->delete();
            db($this->CommentTable)->where('blogid', $PostDetail['id'])->delete();
            db($this->ChapterTable)->where('box', $PostDetail['id'])->delete();
            db($this->FileTable)->where('blogid', $PostDetail['id'])->delete();
            $this->TagsDeleteByPost($PostDetail['id']);
        }
        return null;
    }

    //===== DÀNH CHO BÀI VIẾT =====//
    public function PostListSticked($limit = 10)
    {
        return db($this->PostTable)
            ->selectRaw("{$this->PostTable}.*, {$this->CategoryTable}.id AS category_id, {$this->CategoryTable}.name AS category_name, (SELECT author FROM {$this->CommentTable} WHERE blogid = {$this->PostTable}.id ORDER BY id DESC LIMIT 1) AS last_comment_author")
            ->leftJoin($this->CategoryTable, "{$this->PostTable}.category", '=', "{$this->CategoryTable}.id")
            ->where("{$this->PostTable}.sticked", 1)
            ->orderBy("{$this->PostTable}.update_time", 'DESC')
            ->limit((int)$limit)
            ->get();
    }

    public function PostList($category_id = 0, $per = 10, $order_by = 'update_time', $sort = 'desc', $start = 0)
    {
        $CategoryExist = false;
        if ($category_id > 0 && $this->CategoryExist($category_id)) {
            $CategoryExist = true;
        }

        if (!in_array($order_by, Env::getList('POST_LIST_ORDER_BY', ['id', 'time', 'update_time', 'view']))) {
            $order_by = 'update_time';
        }
        if (!in_array($sort, Env::getList('POST_LIST_SORT', ['asc', 'desc']))) {
            $sort = 'desc';
        }

        $query = db($this->PostTable)
            ->selectRaw("{$this->PostTable}.*, (CASE WHEN {$this->CategoryTable}.id IS NOT NULL THEN {$this->CategoryTable}.id ELSE NULL END) AS category_id, (CASE WHEN {$this->CategoryTable}.id IS NOT NULL THEN {$this->CategoryTable}.name ELSE NULL END) AS category_name")
            ->leftJoin($this->CategoryTable, "{$this->PostTable}.category", '=', "{$this->CategoryTable}.id");

        if ($CategoryExist) {
            $query->where("{$this->CategoryTable}.id", (int)$category_id);
        }

        return $query
            ->orderBy("{$this->PostTable}.{$order_by}", $sort)
            ->limit((int)$per)
            ->offset((int)$start)
            ->get();
    }

    public function PostListSimilar($category_id, $post_id)
    {
        return db($this->PostTable)
            ->where('category', (int)$category_id)
            ->where('id', '!=', (int)$post_id)
            ->orderByRaw('RAND()')
            ->limit(5)
            ->get();
    }

    public function PostListUser($nick, $start = 0, $end = 10)
    {
        return db($this->PostTable)
            ->selectRaw("{$this->PostTable}.*, (CASE WHEN {$this->CategoryTable}.id IS NOT NULL THEN {$this->CategoryTable}.id ELSE NULL END) AS category_id, (CASE WHEN {$this->CategoryTable}.id IS NOT NULL THEN {$this->CategoryTable}.name ELSE NULL END) AS category_name")
            ->leftJoin($this->CategoryTable, "{$this->PostTable}.category", '=', "{$this->CategoryTable}.id")
            ->where("{$this->PostTable}.author", (string)$nick)
            ->orderBy("{$this->PostTable}.update_time", 'DESC')
            ->limit((int)$end)
            ->offset((int)$start)
            ->get();
    }

    public function PostCountUser($nick)
    {
        return db($this->PostTable)->where('author', $nick)->count();
    }

    public function PostDetail($id)
    {
        return db($this->PostTable)->where('id', (int)$id)->first();
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
                db($this->PostTable)->where('id', (int)$id)->update(['like' => $PostLikeListSave]);
                $system_notify_content = '@' . $nick . ' vừa bày tỏ cảm xúc trong một bài viết có mặt bạn. [url=/articles/' . $id . '-' . $PostDetail['slug'] . '.html][XEM BÀI VIẾT][/url]';
                db('mail')->insert([
                    'sender_receiver' => $PostDetail['author'] . '_' . (string) Env::get('USER_BOT', 'SystemBOT'),
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
        $allowedCols = ['blocked', 'sticked', 'view', 'category', 'title', 'slug', 'content', 'update_time', 'meta_data'];
        if (!in_array($col, $allowedCols, true)) {
            return 0;
        }
        return db($this->PostTable)->where('id', (int)$id)->update([$col => $cold_val]);
    }

    public function PostPublish($category, $title, $slug, $content, $author, $meta_data = null)
    {
        return db($this->PostTable)->insert([
            'category'    => $category,
            'title'       => $title,
            'slug'        => _e($slug),
            'content'     => $content,
            'author'      => $author,
            'time'        => TIME,
            'update_time' => TIME,
            'meta_data'   => $meta_data,
        ]);
    }

    public function PostEdit($id, $data)
    {
        return db($this->PostTable)->where('id', (int)$id)->update([
            'category'  => $data['category'],
            'title'     => $data['title'],
            'slug'      => _e($data['slug']),
            'content'   => $data['content'],
            'meta_data' => $data['meta_data'] ?? null,
        ]);
    }

    public function PostDelete($id)
    {
        db($this->PostTable)->where('id', (int)$id)->delete();
        db($this->CommentTable)->where('blogid', (int)$id)->delete();
        db($this->ChapterTable)->where('box', (int)$id)->delete();
        db($this->FileTable)->where('blogid', (int)$id)->delete();
        $this->TagsDeleteByPost($id);
    }

    public function PostViewUpdate($PostDetail)
    {
        $PostID = $PostDetail['id'];
        if (!isset($_SESSION['post_viewed_' . $PostID])) {
            db($this->PostTable)->where('id', (int)$PostDetail['id'])->update([
                'view' => $PostDetail['view'] + 1,
            ]);
            $_SESSION['post_viewed_' . $PostID] = true;
        }
        return $PostDetail['view'];
    }

    //===== DÀNH CHO CHƯƠNG BÀI VIẾT =====//
    public function ChapterList($post_id)
    {
        $query = db($this->ChapterTable);
        if ($post_id > 0) {
            $query->where('box', (int)$post_id);
        }
        return $query->orderBy('id', 'desc')->get();
    }

    public function ChapterEdit($id, $data)
    {
        return db($this->ChapterTable)
            ->where('id', $id)
            ->update([
                'title' => _e($data['title']),
                'slug' => _e($data['slug']),
                'content' => _e($data['content']),
                'meta_data' => $data['meta_data'] ?? null,
            ]);
    }

    public function ChapterDelete($id)
    {
        return db($this->ChapterTable)->where('id', $id)->delete();
    }

    public function ChapterPublish($box, $title, $slug, $content, $author, $meta_data = null)
    {
        return db($this->ChapterTable)->insert([
            'box' => $box,
            'title' => _e($title),
            'slug' => _e($slug),
            'content' => _e($content),
            'author' => $author,
            'time' => TIME,
            'meta_data' => $meta_data,
        ]);
    }

    //===== DÀNH CHO BÌNH LUẬN =====//
    public function CommentList($post_id, $start, $end)
    {
        return db($this->CommentTable)
            ->where('blogid', (int)$post_id)
            ->orderBy('id', 'asc')
            ->limit((int)$end)
            ->offset((int)$start)
            ->get();
    }

    public function CommentSend($PostDetail, $author, $comment)
    {
        // lưu bình luận
        db($this->CommentTable)->insert([
            'blogid' => $PostDetail['id'],
            'author' => $author,
            'comment' => $comment,
            'time' => TIME,
        ]);
        // cập nhật thời gian cho post
        db($this->PostTable)
            ->where('id', $PostDetail['id'])
            ->update(['update_time' => TIME]);
        if ($author != $PostDetail['author']) {
            // lưu vào thông báo của author post
            $system_notify_content = '@' . $author . ' đã bình luận trong một bài viết của bạn. [url=/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html][XEM BÀI VIẾT] ' . $PostDetail['title'] . '[/url]';
            $e2e = new E2E();
            $encrypted_notify = $e2e->encrypt($system_notify_content, (string) Env::get('E2E_SECRET_KEY', ''), 'mail' . $author);
            db('mail')->insert([
                'sender_receiver' => $PostDetail['author'] . '_' . (string) Env::get('USER_BOT', 'SystemBOT'),
                'nick' => $author,
                'content' => $encrypted_notify,
                'time' => TIME,
                'view' => 'no',
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
                    if ($row = db('chat')->select(['name'])->where('id', $quoteId)->first()) {
                        $quoteAuthorNick = $row['name'];
                        $quoteUrl = '[url=/shoutbox]trò chuyện[/url]';
                    }
                } elseif ($module === 'articles') {
                    if ($row = db('articles_cmt')->select(['author', 'blogid'])->where('id', $quoteId)->first()) {
                        $quoteAuthorNick = $row['author'];
                        $quoteUrl = '[url=/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html]bài viết ' . $PostDetail['title'] . '[/url]';
                    }
                }

                if ($quoteAuthorNick && !in_array($quoteAuthorNick, $notified_users)) {
                    $notified_users[] = $quoteAuthorNick;
                    $system_notify_content = '@' . $author . ' đã trích dẫn bình luận của bạn trong ' . $quoteUrl;
                    
                    $bot_name = (string) Env::get('USER_BOT', 'SystemBOT');
                    
                    $e2e = new E2E();
                    $encrypted_notify = $e2e->encrypt($system_notify_content, (string) Env::get('E2E_SECRET_KEY', ''), 'mail' . $author);
                    db('mail')->insert([
                        'sender_receiver' => $quoteAuthorNick . '_' . $bot_name,
                        'nick' => $author,
                        'content' => $encrypted_notify,
                        'time' => TIME,
                        'view' => 'no',
                    ]);
                }
            } catch (Exception $e) {}
        }

        return null;
    }

    //===== PHẦN DÀNH CHO CÁC LƯU TRỮ ĐÃ CŨ =====//
    public function FileList($per, $start)
    {
        return db($this->FileTable)
            ->orderBy('id', 'desc')
            ->limit((int)$per)
            ->offset((int)$start)
            ->get();
    }

    public function FileListForPost($PostDetail)
    {
        return db($this->FileTable)
            ->where('blogid', (int)$PostDetail['id'])
            ->orderBy('id', 'desc')
            ->get();
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
        $price = max(0, (int)($FileDetail['price'] ?? 0));
        $boughtList = $this->FilePurchaserList($FileDetail['id']);
        if (in_array($MyDetail['nick'], $boughtList, true)) {
            return 'AlreadyBought';
        }

        // Trừ xu nguyên tử (atomic check xu >= price)
        $stmtPurchaser = Database::pdo()->prepare('UPDATE `users` SET `xu` = `xu` - :price WHERE `nick` = :nick AND `xu` >= :price');
        $stmtPurchaser->execute([
            'price' => $price,
            'nick' => $MyDetail['nick']
        ]);
        if ($stmtPurchaser->rowCount() === 0) {
            return 'InsufficientFunds';
        }

        // Cộng xu cho tác giả tập tin
        if ($price > 0 && !empty($FileDetail['author']) && $FileDetail['author'] !== $MyDetail['nick']) {
            db('users')->where('nick', $FileDetail['author'])->increment('xu', $price);
        }

        // Thêm chủ đầu tư vào danh sách đã mua
        db($this->FileTable)
            ->where('id', $FileDetail['id'])
            ->update(['mua' => ($MyDetail['nick'] . '.' . $FileDetail['mua'])]);
        return 'Success';
    }

    public function FileDelete($id)
    {
        return db($this->FileTable)->where('id', (int)$id)->delete();
    }



    // Upload Telegram
    public function FileUploadTelegram($PostDetail, $data)
    {
        return db($this->FileTable)->insert([
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
            'blogid' => $PostDetail['id'],
        ]);
    }

    //===== DÀNH CHO TAGS =====//
    public function TagGetBySlug($slug)
    {
        return db($this->TagTable)->where('slug', $slug)->first();
    }

    public function TagsGetByPost($post_id)
    {
        return db($this->TagTable)
            ->select(["{$this->TagTable}.*"])
            ->join($this->PostTagTable, "{$this->TagTable}.id", '=', "{$this->PostTagTable}.tag_id")
            ->where("{$this->PostTagTable}.post_id", $post_id)
            ->get();
    }

    public function PostListByTag($tag_id, $per = 10, $start = 0)
    {
        return db($this->PostTable)
            ->selectRaw("{$this->PostTable}.*, (CASE WHEN {$this->CategoryTable}.id IS NOT NULL THEN {$this->CategoryTable}.id ELSE NULL END) AS category_id, (CASE WHEN {$this->CategoryTable}.id IS NOT NULL THEN {$this->CategoryTable}.name ELSE NULL END) AS category_name")
            ->leftJoin($this->CategoryTable, "{$this->PostTable}.category", '=', "{$this->CategoryTable}.id")
            ->join($this->PostTagTable, "{$this->PostTable}.id", '=', "{$this->PostTagTable}.post_id")
            ->where("{$this->PostTagTable}.tag_id", (int)$tag_id)
            ->orderBy("{$this->PostTable}.update_time", 'DESC')
            ->limit((int)$per)
            ->offset((int)$start)
            ->get();
    }

    public function ForumStatsByTag($tag_id)
    {
        return db($this->PostTagTable)->where('tag_id', (int)$tag_id)->count();
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
            $tag_row = db($this->TagTable)->select(['id'])->where('slug', $slug)->first();

            if ($tag_row) {
                $tag_id = $tag_row['id'];
            } else {
                // Create new tag
                $tag_id = db($this->TagTable)->insert([
                    'name' => $name,
                    'slug' => $slug,
                    'count' => 0,
                ]);
            }
            $new_tag_ids[] = $tag_id;
        }

        // 2. Identify tags to add and remove
        $tags_to_add = array_diff($new_tag_ids, $current_tag_ids);
        $tags_to_remove = array_diff($current_tag_ids, $new_tag_ids);

        // 3. Add new tag relations
        foreach ($tags_to_add as $tag_id) {
            $exists = db($this->PostTagTable)
                ->where('post_id', $post_id)
                ->where('tag_id', $tag_id)
                ->exists();
            if (!$exists) {
                db($this->PostTagTable)->insert([
                    'post_id' => $post_id,
                    'tag_id'  => $tag_id,
                ]);
            }

            db($this->TagTable)->where('id', $tag_id)->increment('count');
        }

        // 4. Remove old tag relations
        foreach ($tags_to_remove as $tag_id) {
            db($this->PostTagTable)
                ->where('post_id', $post_id)
                ->where('tag_id', $tag_id)
                ->delete();

            db($this->TagTable)
                ->where('id', $tag_id)
                ->where('count', '>', 0)
                ->decrement('count');
        }
    }

    public function TagsDeleteByPost($post_id)
    {
        $current_tags_rows = $this->TagsGetByPost($post_id);
        foreach ($current_tags_rows as $row) {
            $tag_id = $row['id'];
            // Decrement count
            db($this->TagTable)
                ->where('id', $tag_id)
                ->where('count', '>', 0)
                ->decrement('count');
        }
        db($this->PostTagTable)->where('post_id', $post_id)->delete();
    }
}