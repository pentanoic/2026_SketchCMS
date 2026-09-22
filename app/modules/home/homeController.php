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

class homeController extends Controller
{
    private articlesModel $articlesModel;
    private userModel $userModel;

    function __construct()
    {
        parent::__construct();
        $this->articlesModel = $this->load->model('articles');
        $this->userModel = $this->load->model('user');
    }

    public function index()
    {
        // tiêu đề trang
        $page_title = config('system.app.name');

        // lọc bài viết
        $isArticles = false;
        $PostCount = $this->articlesModel->ForumStats()['count_post'];
        $page_query = '?';
        # số bài viết có trong 1 trang
        $per = 10;
        $page = $this->request->getVar('page', 1);
        $page = _e($page);
        $page = intval($page);
        if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
            $page = 1;
        }
        # lọc theo chuyên mục
        $NewsBool = false;
        $category_id = $this->request->getVar('category', 0);
        $category_id = _e($category_id);
        $category_id = intval($category_id);
        $isInCategory = false;
        $CategoryName = null;
        $CategorySlug = null;
        $CanPostPublish = false;
        if ($category_id >= 1) {
            $CategoryDetail = $this->articlesModel->CategoryDetail($category_id);

            if ($CategoryDetail && $CategoryDetail['id'] == $category_id) {
                $CategoryName = $CategoryDetail['name'];
                $CategorySlug = $CategoryDetail['slug'];
                $page_title = 'Chuyên mục: ' . $CategoryName;
                
                $page_description = $CategoryDetail['content'] ?? config('system.app.description');
                $page_keyword = $CategoryDetail['keyword'] ?? config('system.app.keyword');
                
                // Xử lý SEO Metadata cho Category
                if (!empty($CategoryDetail['meta_data'])) {
                    $seoData = json_decode($CategoryDetail['meta_data'], true);
                    if ($seoData) {
                        $page_title = !empty($seoData['meta_title']) ? $seoData['meta_title'] : $page_title;
                        $page_description = !empty($seoData['meta_desc']) ? $seoData['meta_desc'] : $page_description;
                        $page_keyword = !empty($seoData['meta_keywords']) ? $seoData['meta_keywords'] : $page_keyword;
                    }
                }
                
                $page_query .= 'category=' . $category_id . '&';
                $PostCount = $this->articlesModel->ForumStats('count_post_in_category', $category_id);
                $isArticles = true;

                if ($CategoryDetail['slug'] == 'news') {
                    $NewsBool = true;
                } else {
                    $isInCategory = true;
                }

                if ($this->request->user()->isLogin) {
                    $MyDetail = $this->request->user()->user;
                    $CanPostPublish = true;

                    if ($CategoryDetail['id'] == NewsID && $MyDetail['level'] < 120) {
                        $CanPostPublish = false;
                    }
                }
            }
        }

        # lọc theo trường
        $order_by = $this->request->getVar('order_by', 'update_time');
        $order_by = mb_strtolower($order_by);
        $order_by = htmlspecialchars($order_by);
        if (in_array($order_by, config('system.PostList.order_by'))) {
            $page_query .= 'order_by=' . $order_by . '&';
        } else {
            $order_by = 'update_time';
        }
        # sắp xếp bài viết theo trường đã lọc
        $sort = $this->request->getVar('sort', 'desc');
        $sort = mb_strtolower($sort);
        $sort = htmlspecialchars($sort);
        if (in_array($sort, config('system.PostList.sort'))) {
            $page_query .= 'sort=' . $sort . '&';
        } else {
            $sort = 'desc';
        }
        # thêm điều kiện cho Forum
        if ($page > 1 || $order_by != 'update_time' || $sort != 'desc') {
            $isArticles = true;
            if ($page_title == config('system.app.name') && $this->request->user()->isLogin) {
                $page_title = 'Diễn đàn';
            }
        }
        # thêm số trang
        if ($page >= 1) {
            $page_query .= 'page=';
        }

        $page_max = ceil($PostCount / $per);
        $start = ($page - 1) * $per;

        // lấy danh sách bài đăng theo bộ lọc và sắp xếp
        $getPostList = $this->articlesModel->PostList($category_id, $per, $order_by, $sort, $start);
        $PostList = [];
        foreach ($getPostList as $PostDetail) {
            $author = mb_strtolower($PostDetail['author']);
            $PostDetail['UserDetail'] = $this->userModel->UserDetailWithFields('nick', $author);
            $PostDetail['chapter'] = $this->articlesModel->ForumStats('count_chapter_in_post', $PostDetail['id']);
            $PostDetail['comment'] = $this->articlesModel->ForumStats('count_comment_in_post', $PostDetail['id']);

            $PostList[] = $PostDetail;
        }

        // lấy danh sách bài đăng sticked theo limit
        $getPostListSticked = $this->articlesModel->PostListSticked(5);
        $PostListSticked = [];
        foreach ($getPostListSticked as $PostDetail) {
            $last_comment_author = mb_strtolower($PostDetail['last_comment_author']);
            $PostDetail['UserDetail_LastCommentAuthor'] = $this->userModel->UserDetailWithFields('nick', $last_comment_author);
            $PostDetail['comment'] = $this->articlesModel->ForumStats('count_comment_in_post', $PostDetail['id']);

            $PostListSticked[] = $PostDetail;
        }

        //$UpdateIfNotMD5 = $this->UserModel->UpdateIfNotMD5();
        $pathHome = 'home';


        $tietNguyenDan2025 = strtotime('2025-01-29');
        if (time() < $tietNguyenDan2025) {
            $pathHome = 'countdown';
        }


        // trả về template
        $v = view()->setTitle($page_title);
        if (isset($page_description)) $v->setDescription($page_description);
        if (isset($page_keyword)) $v->setKeyword($page_keyword);
        
        return $v->render($pathHome, [
            'CategoryList' => $this->articlesModel->CategoryList(20),
            'ForumStats' => $this->articlesModel->ForumStats(),
            'PostCount' => $PostCount,
            'PostListSticked' => $PostListSticked,
            'PostList' => $PostList,
            'PostListPaging' => paging($page_query, $page, $page_max),
            'PostListConfig' => [
                'allow_order_by' => config('system.PostList.order_by'),
                'allow_sort' => config('system.PostList.sort'),
                'order_by' => $order_by,
                'sort' => $sort
            ],
            'isArticles' => $isArticles,
            'NewsBool' => $NewsBool,
            'UserListOnline' => $this->userModel->UserListOnline(),

            'CategoryName' => $CategoryName,
            'CategorySlug' => $CategorySlug,
            'isInCategory' => $isInCategory,
            'CanPostPublish' => $CanPostPublish
        ]);
    }

    public function error()
    {
        return view('404');
    }

    public function faq($page = 'terms')
    {
        $allowed_pages = ['terms', 'about', 'help'];
        if (!in_array($page, $allowed_pages)) {
            $page = 'terms';
        }
        
        $titles = [
            'terms' => 'Nội Quy & Điều Khoản Sử Dụng',
            'about' => 'Về Chúng Tôi (Dorew.ovh)',
            'help' => 'Trung Tâm Trợ Giúp'
        ];
        
        return view('home/faq/' . $page, [
            'page_title' => $titles[$page],
            'current_page' => $page
        ]);
    }

    private function staticPage_getPageTitle($file)
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return '';
        }

        $pattern = '/<!---\s*title\s*:\s*(.*?)\s*-->/s';

        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function staticPage_getContent($file)
    {
        $content = file_get_contents($file);
        return $content;
    }

    public function staticPage($slug = '')
    {
        if (empty($slug)) {
            redirect('/404');
        }

        // Secure the slug against directory traversal
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);

        $templateName = ltrim(get_template(), '/');
        $tplDir = TEMPLATES . '_assets/static-page/';
        $tplPath = $tplDir . $slug . '.php';

        // Không cho phép truy cập --layout trực tiếp
        if (!file_exists($tplPath)) {
            redirect('/404');
        }

        $files = glob($tplDir . '*.php');
        $pages = [];

        if ($files) {
            foreach ($files as $file) {
                $filename = basename($file, '.php');

                $pages[] = [
                    'slug'  => $filename,
                    'title' => $this->staticPage_getPageTitle($file)
                ];
            }

            // Sắp xếp theo slug
            usort($pages, function ($a, $b) {
                return strcmp($a['slug'], $b['slug']);
            });
        }

        $currentIndex = array_search(
            $slug,
            array_column($pages, 'slug')
        );

        $prevPage = (
            $currentIndex !== false &&
            $currentIndex > 0
        )
            ? $pages[$currentIndex - 1]
            : null;

        $nextPage = (
            $currentIndex !== false &&
            $currentIndex < count($pages) - 1
        )
            ? $pages[$currentIndex + 1]
            : null;

        $prevSlug  = $prevPage['slug'] ?? null;
        $prevTitle = $prevPage['title'] ?? null;

        $nextSlug  = $nextPage['slug'] ?? null;
        $nextTitle = $nextPage['title'] ?? null;

        $title = $this->staticPage_getPageTitle($tplPath);
        $content = $this->staticPage_getContent($tplPath);
        if (empty($title)) {
            $title = ucwords(
                str_replace(['-', '_'], ' ', $slug)
            );
        }


        $template = view();
        $engine = $template->getEngine();
        $engine->registerFunction('thePrev', function ($format, $default) use ($prevSlug, $prevTitle) {

            if ($prevSlug) {

                $url = url($prevSlug . '.html');

                $title = !empty($prevTitle)
                    ? $prevTitle
                    : ucwords(
                        str_replace(
                            ['-', '_'],
                            ' ',
                            $prevSlug
                        )
                    );

                $titleEscaped = htmlspecialchars(
                    $title,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $link =
                    '<a class="cyber-nav-btn" href="' . $url . '" title="' . $titleEscaped . '">' .
                        '<i class="fa-solid fa-arrow-left"></i> ' .
                        $titleEscaped .
                    '</a>';

                echo str_replace('%s', $link, $format);

            } else {

                echo $default;

            }
        });
        $engine->registerFunction('theNext', function ($format, $default) use ($nextSlug, $nextTitle) {

            if ($nextSlug) {

                $url = url($nextSlug . '.html');

                $title = !empty($nextTitle)
                    ? $nextTitle
                    : ucwords(
                        str_replace(
                            ['-', '_'],
                            ' ',
                            $nextSlug
                        )
                    );

                $titleEscaped = htmlspecialchars(
                    $title,
                    ENT_QUOTES,
                    'UTF-8'
                );

                $link =
                    '<a class="cyber-nav-btn" href="' . $url . '" title="' . $titleEscaped . '">' .
                        $titleEscaped .
                        ' <i class="fa-solid fa-arrow-right"></i>' .
                    '</a>';

                echo str_replace('%s', $link, $format);

            } else {

                echo $default;

            }
        });

        return view(
            'static_page',
            [
                'slug'  => $slug,
                'page_title' => $title,
                'content' => $content
            ]
        );
    }

}