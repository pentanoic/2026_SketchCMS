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

class articlesController extends Controller
{
    private articlesModel $articlesModel;
    private userModel $userModel;
    private articlesLibrary $articlesLibrary;
    private $prefix = 'articles_';
    private $PostTable;
    private $CategoryTable;
    private $CommentTable;
    private $ChapterTable;
    private $FileTable;

    function __construct()
    {
        parent::__construct();
        $this->articlesModel = $this->load->model('articles');
        $this->userModel = $this->load->model('user');
        $this->articlesLibrary = $this->load->library('articles');

        $this->PostTable = $this->prefix . 'post';
        $this->CategoryTable = $this->prefix . 'category';
        $this->CommentTable = $this->prefix . 'cmt';
        $this->ChapterTable = $this->prefix . 'chap';
        $this->FileTable = $this->prefix . 'file';
    }

    /* ===== TÌM KIẾM ===== */

    public function Search()
    {
        $query = $this->request->getVar('q');
        $query = $this->articlesLibrary->TrimContent($query);
        $query = isset($query) ? $query : 'Empty';
        $search = $this->articlesModel->ForumSearch($query);

        $SearchResultList = $search['result'];
        $SearchResultCount = $search['count'];
        # số bài viết có trong 1 trang
        $per = 15;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
            $page = 1;
        }
        # thêm số trang
        if ($page >= 1) {
            $page_query = '?page=';
        }

        $page_max = ceil($SearchResultCount / $per);
        $start = ($page - 1) * $per;
        $end = $start + $per;
        if ($end >= $SearchResultCount) {
            $end = $SearchResultCount;
        }

        $SearchResultList = array_slice($SearchResultList, $start, $per);
        $SearchPaging = paging("?q=$query&page=", $page, $page_max);

        return view()->setTitle('Tìm kiếm')->render('articles/search', [
            'SearchQuery' => $query,
            'SearchResultList' => $SearchResultList,
            'SearchResultCount' => $SearchResultCount,
            'SearchPaging' => $SearchPaging
        ]);
    }

    public function TagDetail($TagSlug)
    {
        $TagDetail = $this->articlesModel->TagGetBySlug($TagSlug);
        if (!$TagDetail) {
            redirect('/404');
        }

        $page_title = 'Bài viết gắn thẻ: ' . $TagDetail['name'];
        $PostCount = $this->articlesModel->ForumStatsByTag($TagDetail['id']);

        $per = 10;
        $page = $this->request->getVar('page', 1);
        $page = htmlspecialchars($page);
        $page = intval($page);
        if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
            $page = 1;
        }
        if ($page >= 1) {
            $page_query = '?page=';
        }

        $page_max = ceil($PostCount / $per);
        $start = ($page - 1) * $per;

        $PostListRaw = $this->articlesModel->PostListByTag($TagDetail['id'], $per, $start);
        $PostList = [];
        foreach ($PostListRaw as $PostItem) {
            // đếm bình luận
            $PostItem['CommentCount'] = $this->articlesModel->ForumStats('count_comment_in_post', $PostItem['id']);
            $PostList[] = $PostItem;
        }

        return view()->setTitle($page_title)->render('articles/tag_detail', [
            'TagDetail' => $TagDetail,
            'PostList' => $PostList,
            'PostPaging' => paging($page_query, $page, $page_max),
            'PostCount' => $PostCount
        ]);
    }

    /* ===== BÀI VIẾT ==== */
    public function PostDetail($PostSlug)
    {
        $MyDetail = [];
        $isAdminArticles = false;
        $AdminActionResult = null;
        $isPersonCanAction = false;
        $isInLikeList = false;
        if ($this->request->user()->isLogin) {
            $MyDetail = $this->request->user()->user;
        }

        $error = null;
        $page_title = 'Diễn đàn';
        $pattern = '/^(?P<id>\d+)-(?P<slug>[a-zA-Z0-9\-_]+)$/';
        if (preg_match($pattern, $PostSlug, $matches)) {
            $id = intval($matches['id']);
            $PostDetail = $this->articlesModel->PostDetail($id);
            if (!$PostDetail) {
                $error = 'Không tìm thấy bài viết';
            }
            $page_title = $PostDetail['title'];
            $page_description = $this->articlesLibrary->PageDescription($PostDetail['content']);
            $page_keyword = $this->articlesLibrary->PageKeyword($page_title);
            
            // Xử lý SEO Metadata
            if (!empty($PostDetail['meta_data'])) {
                $seoData = json_decode($PostDetail['meta_data'], true);
                if ($seoData) {
                    $page_title = !empty($seoData['meta_title']) ? $seoData['meta_title'] : $page_title;
                    $page_description = !empty($seoData['meta_desc']) ? $seoData['meta_desc'] : $page_description;
                    $page_keyword = !empty($seoData['meta_keywords']) ? $seoData['meta_keywords'] : $page_keyword;
                }
            }
            
            $PostDetail['url'] = url('/articles/') . $id . '-' . $PostDetail['slug'] . '.html';
            if (preg_match('/\[img\](.*?)\[\/img\]/', $PostDetail['content'], $matches)) {
                $PostDetail['thumbnail'] = $matches[1];
            } else {
                $PostDetail['thumbnail'] = 0;
            }
            $PostDetail['content'] = $this->articlesLibrary->bbcode($PostDetail['content']);
            $PostDetail['view'] = $this->articlesModel->PostViewUpdate($PostDetail);

            $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);
            $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']);
            $ChapterList = $this->articlesModel->ChapterList($PostDetail['id']);
            $PostListSimilar = $this->articlesModel->PostListSimilar($PostDetail['category'], $id);
            $TagsList = $this->articlesModel->TagsGetByPost($PostDetail['id']);

            // Thống kê trong bài viết
            $ForumStats = [
                'count_post_in_category' => $this->articlesModel->ForumStats('count_post_in_category', $PostDetail['category']),
                'count_chapter_in_post' => $this->articlesModel->ForumStats('count_chapter_in_post', $PostDetail['id']),
                'count_comment_in_post' => $this->articlesModel->ForumStats('count_comment_in_post', $PostDetail['id']),
                'count_file_in_post' => $this->articlesModel->ForumStats('count_file_in_post', $PostDetail['id']),
            ];

            // Danh sách file
            $FileList = $this->articlesModel->FileListForPost($PostDetail);

            // Comments
            $CommentCount = $ForumStats['count_comment_in_post'];
            # số bài viết có trong 1 trang
            $per = 10;
            $page = $this->request->getVar('page', 1);
            $page = htmlspecialchars($page);
            $page = intval($page);
            if (preg_match('/[a-zA-Z]|%/', $page) || $page < 1) {
                $page = 1;
            }
            # thêm số trang
            if ($page >= 1) {
                $page_query = '?page=';
            }

            $page_max = ceil($CommentCount / $per);
            $start = ($page - 1) * $per;
            $end = $start + $per;
            if ($end >= $CommentCount) {
                $end = $CommentCount;
            }
            # lấy danh sách comment
            $getCommentList = $this->articlesModel->CommentList($PostDetail['id'], $start, $end);
            $CommentList = [];
            foreach ($getCommentList as $CommentDetail) {
                $author = mb_strtolower($CommentDetail['author']);
                $CommentDetail['UserDetail'] = $this->userModel->UserDetailWithFields('nick', $author);
                $CommentDetail['comment'] = $this->articlesLibrary->bbcode($CommentDetail['comment']);

                $CommentList[] = $CommentDetail;
            }
            # gửi comment
            $isPersonCanComment = true;
            $ReasonCanNotComment = null;
            $errorComment = null;
            if ($this->request->user()->isLogin) {
                $UserDetailBlockList = $this->userModel->UserDetailBlockList($UserDetail)['Get'];
                if ($PostDetail['blocked'] == 1) {
                    $isPersonCanComment = false;
                    $ReasonCanNotComment = 'Chủ đề thảo luận này đã đóng cửa bình luận!';
                }
                if (in_array($MyDetail['nick'], $UserDetailBlockList)) {
                    $isPersonCanComment = false;
                    $ReasonCanNotComment = 'Bạn không thể bình luận trong chủ đề mà có tác giả đang chặn bạn!';
                }
                if (
                    $MyDetail['nick'] == $UserDetail['nick']
                    || $MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level']
                ) {
                    $isPersonCanAction = true;
                }

                if ($isPersonCanComment) {
                    $inputContent = $this->request->postVar('content', '');
                    $inputContent = $this->articlesLibrary->TrimContent($inputContent);
                    $inputContent_len = $this->articlesLibrary->ContentLen($inputContent);
                    if ($this->request->getMethod() === 'POST') {
                        // reset token
                        $token = $this->request->postVar('csrf_token', '');
                        $checktoken = isCSRFTokenValid($token);
                        if ($checktoken) {
                            $error[] = 'Invalid token';
                        }
                        unsetCSRFToken();
                        generateCSRFToken();
                        if (!isset($inputContent)) {
                            $errorComment = 'Vui lòng nhập nội dung bình luận';
                        }
                        if ($inputContent_len < 5 || $inputContent_len > 1200) {
                            $errorComment = 'Nội dung bình luận không hợp lệ';
                        }
                        if (!$errorComment) {
                            $this->articlesModel->CommentSend($PostDetail, $MyDetail['nick'], $inputContent);
                            redirect('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html');
                        }
                    }
                }
            }

            // thao tác với update data
            $mod = $this->request->getVar('mod', '');
            $PostLikeList = $this->articlesModel->PostLikeList($id);
            if ($mod == 'like') {
                $PostLikeSave = $this->articlesModel->PostLikeSave($id, $MyDetail['nick'], 'save');
                if ($PostLikeSave) {
                    redirect('?page=1');
                }
            }
            if ($this->request->user()->isLogin) {
                $isInLikeList = $this->articlesModel->PostLikeSave($id, $MyDetail['nick'], 'check');
            }
            # lấy danh sách người đã like
            $PostLikeListDisplay = '';
            $PostLikeListCount = min(3, $this->articlesModel->PostLikeList($id, 'count'));
            $liked_count = 0;
            foreach (array_slice($PostLikeList, 0, $PostLikeListCount) as $index => $pliked) {
                if ($pliked['id']) {
                    $liked_count++;
                    $PostLikeListDisplay .= RoleColor($pliked);
                    if ($index < $PostLikeListCount - 1) {
                        $PostLikeListDisplay .= ', ';
                    }
                }
            }
            $total_likes = count($PostLikeList);
            if ($total_likes > 4) {
                $additional_likes = $total_likes - 4;
                $PostLikeListDisplay .= ' và ' . $additional_likes . ' người khác';
            }
            if ($liked_count > 0) {
                $PostLikeListDisplay = '<div class="likelist">' . $PostLikeListDisplay . ' đã thích bài viết này</div>';
            }
            # đóng cửa, ghim bài
            $ActionLock = null;
            $ActionLockName = null;
            $ActionPin = null;
            $ActionPinName = null;
            if ($PostDetail['blocked'] == 1) {
                $ActionLock = 'unlock';
                $ActionLockName = '<i class="fa fa-unlock" aria-hidden="true"></i> Mở thảo luận';
            } else if ($PostDetail['blocked'] == 0) {
                $ActionLock = 'lock';
                $ActionLockName = '<i class="fa fa-lock" aria-hidden="true"></i> Đóng thảo luận';
            }
            if ($PostDetail['sticked'] == 1) {
                $ActionPin = 'unpin';
                $ActionPinName = '<i class="fa fa-file-text" aria-hidden="true"></i> Gỡ ghim';
            } else if ($PostDetail['sticked'] == 0) {
                $ActionPin = 'pin';
                $ActionPinName = '<i class="fa fa-thumb-tack" aria-hidden="true"></i> Ghim chủ đề';
            }
            if ($this->request->user()->isLogin) {
                if ($MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level']) {
                    $isAdminArticles = true;
                    if ($mod == 'unlock' && $PostDetail['blocked'] == 1) {
                        $this->articlesModel->PostSaveOne($id, 'blocked', 0);
                        $AdminActionResult = 'Mở cửa chủ đề thành công';
                    } elseif ($mod == 'lock' && $PostDetail['blocked'] == 0) {
                        $this->articlesModel->PostSaveOne($id, 'blocked', 1);
                        $AdminActionResult = 'Đóng cửa chủ đề thành công';
                    } elseif ($mod == 'unpin' && $PostDetail['sticked'] == 1) {
                        $this->articlesModel->PostSaveOne($id, 'sticked', 0);
                        $AdminActionResult = 'Gỡ ghim chủ đề thành công';
                    } elseif ($mod == 'pin' && $PostDetail['sticked'] == 0) {
                        $this->articlesModel->PostSaveOne($id, 'sticked', 1);
                        $AdminActionResult = 'Ghim chủ đề thành công';
                    }
                }
                if ($mod == 'unpin' && $PostDetail['sticked'] == 1 || $mod == 'pin' && $PostDetail['sticked'] == 0) {
                    echo "<script>setTimeout(function (){
                            const urlWithoutQuery = window.location.origin + window.location.pathname;
                            window.location.href = urlWithoutQuery;
                        }, 2000);
                    </script>";
                }
            }
        } else {
            $error = 'Thao tác không hợp lệ';
        }

        if ($error) {
            redirect('/404');
        }

        // return vào lớp view
        return view()
            ->setTitle($page_title)
            ->setDescription($page_description)
            ->setKeyword($page_keyword)
            ->render('articles/post_detail', [
                'CategoryDetail' => $CategoryDetail,
                'PostDetail' => $PostDetail,
                'UserDetail' => $UserDetail,
                'ForumStats' => $ForumStats,
                'ChapterList' => $ChapterList,
                'PostListSimilar' => $PostListSimilar,
                'TagsList' => $TagsList,
                'TotalPostsAndComments' => $this->articlesModel->CountTotalPostsAndComments($UserDetail['nick'] ?? ''),

                'CommentList' => $CommentList,
                'CommentListPaging' => paging($page_query, $page, $page_max),
                'isPersonCanComment' => $isPersonCanComment,
                'ReasonCanNotComment' => $ReasonCanNotComment,

                'FileList' => $FileList,

                'MyDetail' => $MyDetail,
                'isInLikeList' => $isInLikeList,
                'PostLikeList' => $PostLikeList,
                'PostLikeListDisplay' => $PostLikeListDisplay,

                'isAdminArticles' => $isAdminArticles,
                'AdminActionResult' => $AdminActionResult,
                'isPersonCanAction' => $isPersonCanAction,
                'errorComment' => $errorComment,

                'ActionLock' => $ActionLock,
                'ActionLockName' => $ActionLockName,
                'ActionPin' => $ActionPin,
                'ActionPinName' => $ActionPinName
            ]);
    }

    public function PostPublish()
    {
        $HaveCategoryDetail = false;
        $error = [];
        // chuyển hướng nếu không phải là Admin level 120
        $this->userModel->isAdmin120redirect($this->request);
        $MyDetail = $this->request->user()->user;
        $page_title = 'Đăng bài mới';

        // lấy danh sách chuyên mục
        $CategoryList = $this->articlesModel->CategoryList(20);

        // xử lý dữ liệu
        $inputTitle = $this->request->postVar('title', '');
        $inputTitle = $this->articlesLibrary->TrimContent($inputTitle);
        $inputContent = $this->request->postVar('content', '');
        $inputContent = $this->articlesLibrary->TrimContent($inputContent);
        $inputCategory = $this->request->postVar('category', '');
        $inputCategory = intval($inputCategory);
        $inputTags = $this->request->postVar('tags', '');

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            // kiểm tra xem chuyên mục có tồn tại không
            $CategoryCheckExists = $this->articlesModel->ForumCheckExists($this->CategoryTable, 'id', $inputCategory);
            if (!$CategoryCheckExists) {
                $error[] = 'Chuyên mục không tồn tại';
            }

            if (empty($inputTitle) || empty($inputContent)) {
                $error[] = 'Tiêu đề và nội dung không được để trống';
            }
            $inputTitle_len = $this->articlesLibrary->ContentLen($inputTitle);
            if ($inputTitle_len < 5 || $inputTitle_len > 100) {
                $error[] = 'Độ dài tiêu đề phải từ 5 đến 100 ký tự';
            }
            $inputContent_len = $this->articlesLibrary->ContentLen($inputContent);
            if ($inputContent_len < 5 || $inputContent_len > 7000) {
                $error[] = 'Nội dung bài viết không hợp lệ';
            }
            if (!$error) {
                $inputSlug = $this->articlesLibrary->slug($inputTitle);
                $PostCheckExists = $this->articlesModel->ForumCheckExists($this->PostTable, 'slug', $inputSlug);
                if ($PostCheckExists) {
                    $error[] = 'Bài viết này đã tồn tại, nếu bạn muốn tạo 1 bài viết với chủ đề thảo luận tương tự, vui lòng thay đổi tiêu đề';
                }
            }
            if (!$error) {
                // Lấy dữ liệu SEO
                $metaData = [
                    'meta_title' => $this->request->postVar('meta_title', ''),
                    'meta_desc' => $this->request->postVar('meta_desc', ''),
                    'meta_keywords' => $this->request->postVar('meta_keywords', '')
                ];
                $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE);

                $PostID = $this->articlesModel->PostPublish($inputCategory, $inputTitle, $inputSlug, $inputContent, $MyDetail['nick'], $metaDataJson);
                
                // Xử lý Tags
                $tags_array = [];
                $tags_input_parts = explode(',', $inputTags);
                foreach ($tags_input_parts as $part) {
                    $tname = trim($part);
                    if (!empty($tname)) {
                        $tslug = $this->articlesLibrary->slug($tname);
                        if (!empty($tslug)) {
                            $tags_array[] = ['name' => $tname, 'slug' => $tslug];
                        }
                    }
                }
                $this->articlesModel->TagsUpdateForPost($PostID, $tags_array);

                redirect('/articles/' . $PostID . '-' . $inputSlug . '.html');
            } else {
                $error = display_error($error);
            }
        }

        return view()->setTitle($page_title)->render('articles/post_publish', [
            'HaveCategoryDetail' => $HaveCategoryDetail,
            'CategoryList' => $CategoryList,

            'error' => $error,
            'inputTitle' => $inputTitle,
            'inputContent' => $inputContent,
            'inputCategory' => $inputCategory,
            'inputTags' => $inputTags
        ]);
    }

    public function PostPublishOne($CategorySlug,)
    {
        $HaveCategoryDetail = true;
        $error = [];
        // chuyển hướng nếu không phải thành viên
        $this->userModel->isLoginredirect($this->request);
        $MyDetail = $this->request->user()->user;

        // kiểm tra xem chuyên mục có tồn tại không
        $CategoryCheckExists = $this->articlesModel->ForumCheckExists($this->CategoryTable, 'slug', $CategorySlug);
        if (!$CategoryCheckExists) {
            redirect('/');
        }

        // lấy thông tin chuyên mục đang truy cập theo cột slug
        $CategoryDetail = $this->articlesModel->ForumDetailWithFields($this->CategoryTable, 'slug', $CategorySlug);
        $page_title = 'Đăng bài mới - ' . $CategoryDetail['name'];

        // xử lý dữ liệu
        $inputTitle = $this->request->postVar('title', '');
        $inputTitle = $this->articlesLibrary->TrimContent($inputTitle);
        $inputContent = $this->request->postVar('content', '');
        $inputContent = $this->articlesLibrary->TrimContent($inputContent);
        $inputTags = $this->request->postVar('tags', '');

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            if (empty($inputTitle) || empty($inputContent)) {
                $error[] = 'Tiêu đề và nội dung không được để trống';
            }
            $inputTitle_len = $this->articlesLibrary->ContentLen($inputTitle);
            if ($inputTitle_len < 5 || $inputTitle_len > 100) {
                $error[] = 'Độ dài tiêu đề phải từ 5 đến 100 ký tự';
            }
            $inputContent_len = $this->articlesLibrary->ContentLen($inputContent);
            if ($inputContent_len < 5 || $inputContent_len > 7000) {
                $error[] = 'Nội dung bài viết không hợp lệ';
            }
            if (!$error) {
                $inputSlug = $this->articlesLibrary->slug($inputTitle);
                $PostCheckExists = $this->articlesModel->ForumCheckExists($this->PostTable, 'slug', $inputSlug);
                if ($PostCheckExists) {
                    $error[] = 'Bài viết này đã tồn tại, nếu bạn muốn tạo 1 bài viết với chủ đề thảo luận tương tự, vui lòng thay đổi tiêu đề';
                }
            }
            if (!$error) {
                // Lấy dữ liệu SEO
                $metaData = [
                    'meta_title' => $this->request->postVar('meta_title', ''),
                    'meta_desc' => $this->request->postVar('meta_desc', ''),
                    'meta_keywords' => $this->request->postVar('meta_keywords', '')
                ];
                $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE);

                $PostID = $this->articlesModel->PostPublish($CategoryDetail['id'], $inputTitle, $inputSlug, $inputContent, $MyDetail['nick'], $metaDataJson);
                
                // Xử lý Tags
                $tags_array = [];
                $tags_input_parts = explode(',', $inputTags);
                foreach ($tags_input_parts as $part) {
                    $tname = trim($part);
                    if (!empty($tname)) {
                        $tslug = $this->articlesLibrary->slug($tname);
                        if (!empty($tslug)) {
                            $tags_array[] = ['name' => $tname, 'slug' => $tslug];
                        }
                    }
                }
                $this->articlesModel->TagsUpdateForPost($PostID, $tags_array);

                redirect('/articles/' . $PostID . '-' . $inputSlug . '.html');
            } else {
                $error = display_error($error);
            }
        }

        return view()->setTitle($page_title)->render('articles/post_publish', [
            'HaveCategoryDetail' => $HaveCategoryDetail,
            'CategoryDetail' => $CategoryDetail,

            'error' => $error,
            'inputTitle' => $inputTitle,
            'inputContent' => $inputContent,
            'inputTags' => $inputTags
        ]);
    }

    public function ForumEdit($action, $id,)
    {
        $error = [];
        $ChapterDetail = [];
        $PostDetail = [];
        if ($action == 'chapter') {
            // kiểm tra xem chương có tồn tại không
            $ChapterCheckExists = $this->articlesModel->ForumCheckExists($this->ChapterTable, 'id', $id);
            if (!$ChapterCheckExists) {
                redirect('/');
            }
            // lấy thông tin chương
            $ChapterDetail = $this->articlesModel->ForumDetailWithFields($this->ChapterTable, 'id', $id);
            $page_title = 'Sửa chương: ' . $ChapterDetail['title'];
            // lấy thông tin bài viết
            $PostDetail = $this->articlesModel->PostDetail($ChapterDetail['box']);

            // nạp dữ liệu vào input
            $DefaultInputTitle = $ChapterDetail['title'];
            $DefaultInputContent = $ChapterDetail['content'];
            $DefaultInputCategory = $PostDetail['category'];
        } else {
            // kiểm tra xem bài viết có tồn tại không
            $PostCheckExists = $this->articlesModel->ForumCheckExists($this->PostTable, 'id', $id);
            if (!$PostCheckExists) {
                redirect('/');
            }
            // lấy thông tin bài viết
            $PostDetail = $this->articlesModel->PostDetail($id);
            $page_title = 'Sửa bài viết: ' . $PostDetail['title'];

            // nạp dữ liệu vào input
            $DefaultInputTitle = $PostDetail['title'];
            $DefaultInputContent = $PostDetail['content'];
            $DefaultInputCategory = $PostDetail['category'];
            
            // tags
            $current_tags = $this->articlesModel->TagsGetByPost($id);
            $tag_names = array_column($current_tags, 'name');
            $DefaultInputTags = implode(', ', $tag_names);
        }

        // kiểm tra xem có phải tác giả hoặc admin level 120 không
        $this->userModel->isLoginredirect($this->request);
        $MyDetail = $this->request->user()->user;
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']); # thông tin tác giả
        if (!($MyDetail['nick'] == $PostDetail['author'] || $MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level'])) {
            redirect('/');
        }

        // lấy dữ liệu bảng category
        $CategoryList = $this->articlesModel->CategoryList(20);
        $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);

        // xử lý dữ liệu
        $inputTitle = $this->request->postVar('title', $DefaultInputTitle);
        $inputTitle = $this->articlesLibrary->TrimContent($inputTitle);
        $inputContent = $this->request->postVar('content', $DefaultInputContent);
        $inputContent = $this->articlesLibrary->ReadContent($inputContent);
        $inputCategory = $this->request->postVar('category', $DefaultInputCategory);
        $inputCategory = intval($inputCategory);
        $inputTags = $this->request->postVar('tags', $DefaultInputTags ?? '');

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            // kiểm tra xem chuyên mục có tồn tại không
            $CategoryCheckExists = $this->articlesModel->ForumCheckExists($this->CategoryTable, 'id', $inputCategory);
            if (!$CategoryCheckExists) {
                $error[] = 'Chuyên mục không tồn tại';
            }

            if (empty($inputTitle) || empty($inputContent)) {
                $error[] = 'Tiêu đề và nội dung không được để trống';
            }
            $inputTitle_len = $this->articlesLibrary->ContentLen($inputTitle);
            if ($inputTitle_len < 5 || $inputTitle_len > 100) {
                $error[] = 'Độ dài tiêu đề phải từ 5 đến 100 ký tự';
            }
            $inputContent_len = $this->articlesLibrary->ContentLen($inputContent);
            if ($inputContent_len < 5 || $inputContent_len > 7000) {
                $error[] = 'Nội dung bài viết không hợp lệ';
            }
            if (!$error) {
                $inputSlug = $this->articlesLibrary->slug($inputTitle);
                $PostCheckExists = $this->articlesModel->ForumCheckExists($this->PostTable, 'slug', $inputSlug);
                if ($inputSlug != $PostDetail['slug'] && $PostCheckExists) {
                    $error[] = 'Bài viết này đã tồn tại, nếu bạn muốn tạo 1 bài viết với chủ đề thảo luận tương tự, vui lòng thay đổi tiêu đề';
                }
            }
            if (!$error) {
                $inputContent = $this->articlesLibrary->TrimContent($inputContent);
                
                // Lấy dữ liệu SEO
                $metaData = [
                    'meta_title' => $this->request->postVar('meta_title', ''),
                    'meta_desc' => $this->request->postVar('meta_desc', ''),
                    'meta_keywords' => $this->request->postVar('meta_keywords', '')
                ];
                $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE);

                if ($action == 'chapter') {
                    $this->articlesModel->ChapterEdit($id, [
                        'title' => $inputTitle,
                        'slug' => $inputSlug,
                        'content' => $inputContent,
                        'meta_data' => $metaDataJson
                    ]);
                    redirect('/view-chap/' . $id . '-' . $inputSlug . '.html');
                } else {
                    $this->articlesModel->PostEdit($id, [
                        'title' => $inputTitle,
                        'slug' => $inputSlug,
                        'content' => $inputContent,
                        'category' => $inputCategory,
                        'meta_data' => $metaDataJson
                    ]);

                    // Xử lý Tags
                    $tags_array = [];
                    $tags_input_parts = explode(',', $inputTags);
                    foreach ($tags_input_parts as $part) {
                        $tname = trim($part);
                        if (!empty($tname)) {
                            $tslug = $this->articlesLibrary->slug($tname);
                            if (!empty($tslug)) {
                                $tags_array[] = ['name' => $tname, 'slug' => $tslug];
                            }
                        }
                    }
                    $this->articlesModel->TagsUpdateForPost($id, $tags_array);

                    redirect('/articles/' . $id . '-' . $inputSlug . '.html');
                }
            } else {
                $error = display_error($error);
            }
        }

        return view()->setTitle($page_title)->render('articles/articles_edit', [
            'ChapterDetail' => $ChapterDetail,
            'PostDetail' => $PostDetail,
            'CategoryList' => $CategoryList,
            'CategoryDetail' => $CategoryDetail,

            'action' => $action,
            'error' => $error,
            'inputTitle' => $inputTitle,
            'inputContent' => $inputContent,
            'inputCategory' => $inputCategory,
            'inputTags' => $inputTags
        ]);
    }

    public function ForumDelete($action, $id,)
    {
        $error = [];
        $ChapterDetail = [];
        $PostDetail = [];
        if ($action == 'chapter') {
            // kiểm tra xem chương có tồn tại không
            $ChapterCheckExists = $this->articlesModel->ForumCheckExists($this->ChapterTable, 'id', $id);
            if (!$ChapterCheckExists) {
                redirect('/');
            }
            // lấy thông tin chương
            $ChapterDetail = $this->articlesModel->ForumDetailWithFields($this->ChapterTable, 'id', $id);
            $page_title = 'Xóa chương: ' . $ChapterDetail['title'];
            // lấy thông tin bài viết
            $PostDetail = $this->articlesModel->PostDetail($ChapterDetail['box']);
        } else {
            // kiểm tra xem bài viết có tồn tại không
            $PostCheckExists = $this->articlesModel->ForumCheckExists($this->PostTable, 'id', $id);
            if (!$PostCheckExists) {
                redirect('/');
            }
            // lấy thông tin bài viết
            $PostDetail = $this->articlesModel->PostDetail($id);
            $page_title = 'Xóa bài viết: ' . $PostDetail['title'];
        }

        // kiểm tra xem có phải tác giả hoặc admin level 120 không
        $this->userModel->isLoginredirect($this->request);
        $MyDetail = $this->request->user()->user;
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']); # thông tin tác giả
        if (!($MyDetail['nick'] == $PostDetail['author'] || $MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level'])) {
            redirect('/');
        }

        // lấy dữ liệu bảng category
        $CategoryList = $this->articlesModel->CategoryList(20);
        $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            if (!$error) {
                if ($action == 'chapter') {
                    $this->articlesModel->ChapterDelete($id);
                } else {
                    $this->articlesModel->PostDelete($id);
                }
                redirect('/articles');
            } else {
                $error = display_error($error);
            }
        }

        return view()->setTitle($page_title)->render('articles/articles_delete', [
            'ChapterDetail' => $ChapterDetail,
            'PostDetail' => $PostDetail,
            'CategoryList' => $CategoryList,
            'CategoryDetail' => $CategoryDetail,

            'action' => $action,
            'error' => $error
        ]);
    }

    /* ===== UPLOAD FILE ===== */

    private function FileStockage()
    {
        return [
            'xtgem' => 'http://dorew.uiwap.com/storage',
            'gdrive' => 'https://uuou4jgndra8t9flkddfgg.on.drv.tw/file.dorew.ovh',
            'ipfs' => 'https://dweb.link/ipfs/',
            'telegram' => 'https://nosineup.stockage.workers.dev/download'
        ];
    }

    public function FileUpload($id,)
    {
        $error = [];
        $stockage = $this->FileStockage();
        // lấy thông tin bài viết
        $PostDetail = $this->articlesModel->PostDetail($id);
        if (!$PostDetail) {
            redirect('/404');
        }
        // kiểm tra xem có phải tác giả hoặc admin level 120 không
        $this->userModel->isLoginredirect($this->request);
        $MyDetail = $this->request->user()->user;
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']); # thông tin tác giả
        if (!($MyDetail['nick'] == $PostDetail['author'] || $MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level'])) {
            redirect('/');
        }
        // lấy dữ liệu bảng category
        $CategoryList = $this->articlesModel->CategoryList(20);
        $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);

        if ($this->request->getMethod() === 'POST') {
            $filename = $this->request->postVar('filename', '');
            $filename = $this->articlesLibrary->TrimContent($filename);
            $filesize = $this->request->postVar('filesize', 0);
            $filecate = $this->request->postVar('filecate', '');
            $price = $this->request->postVar('price', 0);
            $saleoff = $this->request->postVar('saleoff', 0);
            $condition = $this->request->postVar('condition', 0);
            $status = $this->request->postVar('status', '');
            $status = mb_strtolower($status);
            if (!in_array($status, ['public', 'private'])) {
                $status = 'public';
            }

            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            if (!$filename || !$filesize || !$filecate) {
                $error[] = 'Tải file lên thất bại!';
            }

            if (!$error) {
                $FileID = $this->articlesModel->FileUploadTelegram($PostDetail, [
                    'filename' => $filename,
                    'filesize' => $filesize,
                    'filecate' => $filecate,
                    'status' => $status,
                    'price' => $price,
                    'saleoff' => $saleoff,
                    'condition' => $condition
                ]);
                redirect('/view-file/' . $FileID);
            } else {
                $error = display_error($error);
            }
        }

        return view()->setTitle('Tải lên tập tin')->render('articles/file_upload', [
            'PostDetail' => $PostDetail,
            'CategoryList' => $CategoryList,
            'CategoryDetail' => $CategoryDetail,

            'UploadTo' => 'https://nosineup.stockage.workers.dev/upload',
            'MaxFileSizeAllow' => (1024 * 1024 * 10),
            'error' => $error
        ]);
    }

    public function FileDetail($id,)
    {
        $stockage = $this->FileStockage();

        $error = null;
        $action = $this->request->getVar('action', '');
        $CanDelete = false;
        $FileDetail = $this->articlesModel->ForumDetailWithFields($this->FileTable, 'id', $id);
        if (!$FileDetail) {
            redirect('/404');
        }
        $page_title = 'Thông tin tập tin: ' . $FileDetail['filename'];
        // lấy thông tin bài viết
        $PostDetail = $this->articlesModel->PostDetail($FileDetail['blogid']);
        if (!$PostDetail) {
            redirect('/404');
        }
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']);
        // lấy thông tin chuyên mục
        $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);
        // lấy thông tin tác giả bài viết
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']);
        // lấy thông tin người đã mua
        $FileBoughtCount = $this->articlesModel->FileBoughtCount($id);
        $FilePurchaserList = $this->articlesModel->FilePurchaserList($id);
        $PurchaserList = [];
        if ($FileBoughtCount > 0) {
            foreach ($FilePurchaserList as $Purchaser) {
                $PurchaserList[] = $this->userModel->UserDetailWithFields('nick', $Purchaser);
            }
        }

        // kiểm tra quyền tải xuống
        $CanDownload = false;
        if ($FileDetail['status'] == 'public' || $FileDetail['price'] == 0) {
            $CanDownload = true;
        }
        if ($this->request->user()->isLogin) {
            $MyDetail = $this->request->user()->user;
            if (
                in_array($MyDetail['nick'], $FilePurchaserList)
                || $MyDetail['nick'] == $PostDetail['author']
            ) {
                $CanDownload = true;
            }

            if (!$CanDownload) {
                if ($this->request->getMethod() === 'POST') {
                    // reset token
                    $token = $this->request->postVar('csrf_token', '');
                    $checktoken = isCSRFTokenValid($token);
                    if ($checktoken) {
                        $error = 'Invalid token';
                    }
                    unsetCSRFToken();
                    generateCSRFToken();

                    if ($MyDetail['xu'] < $FileDetail['price']) {
                        $error = 'Tài sản của bạn không đủ để thực hiện thao tác này';
                    }
                    if (!$error) {
                        $this->articlesModel->FileBuy($FileDetail, $MyDetail);
                        redirect('/view-file/' . $FileDetail['id']);
                    }
                }
            }

            // kiểm tra xem có phải tác giả hoặc admin level 120 không
            $CanDelete = ($MyDetail['nick'] == $PostDetail['author'] || $MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level']);
            if ($action == 'delete' && $CanDelete) {
                $this->articlesModel->FileDelete($FileDetail['id']);
                redirect('/articles/' . $PostDetail['id'] . '-' . $PostDetail['slug'] . '.html');
            }
        }

        return view()->setTitle($page_title)->render('articles/file_detail', [
            'Stockage' => $stockage,
            'FileDetail' => $FileDetail,
            'PostDetail' => $PostDetail,
            'CategoryDetail' => $CategoryDetail,
            'UserDetail' => $UserDetail,

            'PurchaserList' => $PurchaserList,
            'BoughtCount' => $FileBoughtCount,

            'CanDelete' => $CanDelete,
            'CanDownload' => $CanDownload,
            'action' => $action,
            'error' => $error
        ]);
    }

    /* ===== CHAPTER ===== */

    public function ChapterPublish($id)
    {
        /**
         * id: khóa id của post thuộc bảng `blog`
         */
        $error = [];
        // kiểm tra xem bài viết có tồn tại không
        $PostCheckExists = $this->articlesModel->ForumCheckExists($this->PostTable, 'id', $id);
        if (!$PostCheckExists) {
            redirect('/');
        }
        // lấy thông tin bài viết
        $PostDetail = $this->articlesModel->PostDetail($id);
        $page_title = 'Thêm chương vào bài viết: ' . $PostDetail['title'];
        // lấy dữ liệu bảng category
        $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);
        // kiểm tra xem có phải tác giả hoặc admin level 120 không
        $this->userModel->isLoginredirect($this->request);
        $MyDetail = $this->request->user()->user;
        $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']); # thông tin tác giả
        if (!($MyDetail['nick'] == $PostDetail['author'] || $MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level'])) {
            redirect('/');
        }

        // xử lý dữ liệu
        $inputTitle = $this->request->postVar('title', '');
        $inputTitle = $this->articlesLibrary->TrimContent($inputTitle);
        $inputContent = $this->request->postVar('content', '');
        $inputContent = $this->articlesLibrary->TrimContent($inputContent);

        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            if (empty($inputTitle) || empty($inputContent)) {
                $error[] = 'Tiêu đề và nội dung không được để trống';
            }
            $inputTitle_len = $this->articlesLibrary->ContentLen($inputTitle);
            if ($inputTitle_len < 5 || $inputTitle_len > 100) {
                $error[] = 'Độ dài tiêu đề phải từ 5 đến 100 ký tự';
            }
            $inputContent_len = $this->articlesLibrary->ContentLen($inputContent);
            if ($inputContent_len < 5 || $inputContent_len > 7000) {
                $error[] = 'Nội dung bài viết không hợp lệ';
            }
            if (!$error) {
                $inputSlug = $this->articlesLibrary->slug($inputTitle);
                $ChapterCheckExists = $this->articlesModel->ForumCheckExists($this->ChapterTable, 'slug', $inputSlug);
                if ($ChapterCheckExists) {
                    $error[] = 'Chương này đã tồn tại, vui lòng thay đổi tiêu đề';
                }
            }
            if (!$error) {
                // Lấy dữ liệu SEO
                $metaData = [
                    'meta_title' => $this->request->postVar('meta_title', ''),
                    'meta_desc' => $this->request->postVar('meta_desc', ''),
                    'meta_keywords' => $this->request->postVar('meta_keywords', '')
                ];
                $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE);

                $ChapterID = $this->articlesModel->ChapterPublish($PostDetail['id'], $inputTitle, $inputSlug, $inputContent, $MyDetail['nick'], $metaDataJson);
                redirect('/view-chap/' . $ChapterID . '-' . $inputSlug . '.html');
            } else {
                $error = display_error($error);
            }
        }

        return view()->setTitle($page_title)->render('articles/chapter_publish', [
            'PostDetail' => $PostDetail,
            'CategoryDetail' => $CategoryDetail,

            'inputTitle' => $inputTitle,
            'inputContent' => $inputContent,

            'error' => $error
        ]);
    }

    public function ChapterDetail($ChapterSlug)
    {
        $MyDetail = [];
        $isPersonCanAction = false;
        $error = null;

        // Pattern regex tách id và slug: ví dụ "123-ten-chuong"
        $pattern = '/^(?P<id>\d+)-(?P<slug>[a-zA-Z0-9\-_]+)$/';

        if (preg_match($pattern, $ChapterSlug, $matches)) {
            $id = intval($matches['id']);

            // 1. Lấy thông tin chương (Giả định bạn có model cho chapter hoặc dùng articlesModel)
            // Thay $this->CategoryTable bằng tên bảng chương của bạn (thường là 'articles_chapter')
            $ChapterDetail = $this->articlesModel->ForumDetailWithFields($this->ChapterTable, 'id', $id);

            if (!$ChapterDetail) {
                $error = 'Không tìm thấy chương này';
            } else {
                $page_title = $ChapterDetail['title'];
                $page_description = $this->articlesLibrary->PageDescription($ChapterDetail['content']);
                $page_keyword = $this->articlesLibrary->PageKeyword($page_title);
                
                // Xử lý SEO Metadata
                if (!empty($ChapterDetail['meta_data'])) {
                    $seoData = json_decode($ChapterDetail['meta_data'], true);
                    if ($seoData) {
                        $page_title = !empty($seoData['meta_title']) ? $seoData['meta_title'] : $page_title;
                        $page_description = !empty($seoData['meta_desc']) ? $seoData['meta_desc'] : $page_description;
                        $page_keyword = !empty($seoData['meta_keywords']) ? $seoData['meta_keywords'] : $page_keyword;
                    }
                }

                // 2. Lấy thông tin bài viết gốc (chứa chương này)
                $PostDetail = $this->articlesModel->PostDetail($ChapterDetail['box']);

                // 3. Lấy thông tin tác giả bài viết & chuyên mục
                $UserDetail = $this->userModel->UserDetailWithFields('nick', $PostDetail['author']);
                $CategoryDetail = $this->articlesModel->CategoryDetail($PostDetail['category']);

                // 4. Cập nhật lượt xem bài viết
                $PostDetail['view'] = $this->articlesModel->PostViewUpdate($PostDetail);

                // 5. Định dạng dữ liệu
                $ChapterDetail['url'] = url('/view-chap/') . $id . '-' . $ChapterDetail['slug'] . '.html';
                $ChapterDetail['content'] = $this->articlesLibrary->bbcode($ChapterDetail['content']);

                $PostListSimilar = $this->articlesModel->PostListSimilar($PostDetail['category'], $PostDetail['id']);
                $ChapterList = $this->articlesModel->ChapterList($PostDetail['id']);

                // 6. Kiểm tra quyền hạn
                if ($this->request->user()->isLogin) {
                    $MyDetail = $this->request->user()->user;
                    if ($MyDetail['nick'] == $PostDetail['author'] || ($MyDetail['level'] >= 120 && $MyDetail['level'] > $UserDetail['level'])) {
                        $isPersonCanAction = true;
                    }
                }

                // 7. Thống kê bài viết
                $ForumStats = [
                    'count_post_in_category' => $this->articlesModel->ForumStats('count_post_in_category', $PostDetail['category']),
                    'count_chapter_in_post' => $this->articlesModel->ForumStats('count_chapter_in_post', $PostDetail['id']),
                    'count_comment_in_post' => $this->articlesModel->ForumStats('count_comment_in_post', $PostDetail['id']),
                ];
            }
        } else {
            $error = 'Thao tác không hợp lệ';
        }

        // Xử lý lỗi
        if ($error) {
            redirect('/404');
        }

        return view()
            ->setTitle($page_title)
            ->setDescription($page_description)
            ->setKeyword($page_keyword)
            ->render('articles/chapter_detail', [
                'CategoryDetail' => $CategoryDetail,
                'PostDetail'     => $PostDetail,
                'ChapterDetail'  => $ChapterDetail,
                'UserDetail'     => $UserDetail,
                'PostListSimilar' => $PostListSimilar,
                'ForumStats'     => $ForumStats,
                'ChapterList'    => $ChapterList,
                'isPersonCanAction' => $isPersonCanAction
            ]);
    }

    /* ===== CHUYÊN MỤC ===== */
    public function CategoryPublish()
    {
        // chuyển hướng nếu không phải Admin level 120
        $this->userModel->isAdmin120redirect($this->request);

        // đếm số lượng chuyên mục hiện có
        $CategoryCount = $this->articlesModel->ForumStats()['count_category'];

        // thao tác dữ liệu
        $CategoryName = $this->request->postVar('name', '');
        $CategoryName = $this->articlesLibrary->TrimContent($CategoryName);
        $CategoryContent = $this->request->postVar('content', '');
        $CategoryContent = $this->articlesLibrary->TrimContent($CategoryContent);
        $CategoryKeyword = $this->request->postVar('keyword', '');
        $CategoryKeyword = $this->articlesLibrary->TrimContent($CategoryKeyword);
        $CategoryID = $this->request->postVar('category_id', '');
        $SubmitButton = $this->request->postVar('submit', 'create');
        $error = [];
        if ($this->request->getMethod() === 'POST') {
            // reset token
            $token = $this->request->postVar('csrf_token', '');
            $checktoken = isCSRFTokenValid($token);
            if ($checktoken) {
                $error[] = 'Invalid token';
            }
            unsetCSRFToken();
            generateCSRFToken();

            // tạo chuyên mục
            if ($SubmitButton == 'create') {
                if (empty($CategoryName) || empty($CategoryContent) || empty($CategoryKeyword)) {
                    $error[] = 'Vui lòng điền đầy đủ thông tin cho chuyên mục';
                }
                $CategoryName_len = $this->articlesLibrary->ContentLen($CategoryName);
                if ($CategoryName_len < 3 || $CategoryName_len > 70) {
                    $error[] = 'Độ dài tiêu đề chuyên mục không hợp lệ (min. 3, max. 70)';
                }
                $CategorySlug = $this->articlesLibrary->slug($CategoryName);
                if ($this->articlesModel->ForumCheckExists($this->CategoryTable, 'slug', $CategorySlug)) {
                    $error[] = 'Chuyên mục này đã tồn tại, nếu bạn muốn tạo 1 chuyên mục có các chủ đề tương tự, xin hãy đổi tên khác';
                }
                if (!$error) {
                    $this->articlesModel->CategoryPublish($CategoryName, $CategorySlug, $CategoryContent, $CategoryKeyword);
                    redirect('/articles/category');
                }
            } elseif ($SubmitButton == 'delete') {
                if (empty($CategoryID)) {
                    $error[] = 'Vui lòng chọn chuyên mục muốn xóa';
                }
                if (!$error) {
                    $this->articlesModel->CategoryDelete($CategoryID);
                    redirect('/articles/category');
                }
            }
        }
        if ($error) {
            $error = display_error($error);
        }

        return view()->setTitle('Quản lý: Chuyên mục')->render('articles/category_publish', [
            'CategoryList' => $this->articlesModel->CategoryList(),
            'CategoryCount' => $CategoryCount,
            'error' => $error
        ]);
    }

    /* ===== SEO SITEMAP ===== */
    // seo sitemap
    public function sitemap()
    {
        $CategoryCount = $this->articlesModel->ForumStats()['count_category'];
        $PostCount = $this->articlesModel->ForumStats()['count_post'];
        $ChapterCount = $this->articlesModel->ForumStats()['count_chapter'];

        $CategoryList = $this->articlesModel->CategoryList();
        $PostList = $this->articlesModel->PostList(0, $PostCount, 'id', 'asc', 0);
        $ChapterList = $this->articlesModel->ChapterList(0);

        return view()->render('articles/_seo.sitemap', [
            'CategoryCount' => $CategoryCount,
            'PostCount' => $PostCount,
            'ChapterCount' => $ChapterCount,

            'CategoryList' => $CategoryList,
            'PostList' => $PostList,
            'ChapterList' => $ChapterList
        ]);
    }
}
