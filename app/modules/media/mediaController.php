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

class mediaController extends Controller
{
    private $mediaConfig = [];
    private mediaModel $mediaModel;

    public function __construct()
    {
        parent::__construct();
        $this->mediaModel = $this->load->model('media');
        // Lấy cấu hình
        $systemConfig = require ROOT . 'system/configs/autoload/system.php';
        $this->mediaConfig = $systemConfig['media'] ?? [
            'telegram_upload_mode' => 'cloudflare',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'cloudflare_worker_url' => ''
        ];

        // Chặn khách, chỉ cho phép người dùng đăng nhập tải file
        if (!$this->request->user()->isLogin) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Vui lòng đăng nhập để sử dụng tính năng này!']);
            exit;
        }
    }

    public function upload()
    {
        if ($this->request->getMethod() !== 'POST') {
            return ['status' => 'error', 'message' => 'Method not allowed'];
        }

        $filename = $this->request->postVar('filename', '');
        $filesize = $this->request->postVar('filesize', 0);
        $filecate = $this->request->postVar('filecate', ''); // Telegram file_id

        // Nếu có upload file trực tiếp (chế độ direct)
        if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
            $filename = $_FILES['document']['name'];
            $filesize = $_FILES['document']['size'];

            $cfile = new \CURLFile($_FILES['document']['tmp_name'], $_FILES['document']['type'], $filename);
            $post = [
                'chat_id' => $this->mediaConfig['telegram_chat_id'],
                'document' => $cfile,
                'disable_content_type_detection' => 'true'
            ];

            $ch = curl_init('https://api.telegram.org/bot' . $this->mediaConfig['telegram_bot_token'] . '/sendDocument');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $result = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($result, true);
            if (isset($data['result']['document']['file_id'])) {
                $filecate = $data['result']['document']['file_id'];
            } else {
                http_response_code(500);
                return ['error' => 'Không thể kết nối Telegram'];
            }
        }

        if (empty($filename) || empty($filecate)) {
            return ['status' => 'error', 'message' => 'Dữ liệu không hợp lệ!'];
        }

        $blogid = isset($_POST['blogid']) ? (int)$_POST['blogid'] : 0;

        // Insert into articles_file
        $data = [
            'time' => time(),
            'filename' => $filename,
            'filecate' => $filecate,
            'filesize' => $filesize,
            'type' => 'telegram',
            'author' => $this->request->user()->user['nick'],
            'status' => 'public',
            'price' => 0,
            'blogid' => $blogid
        ];

        $file_id = $this->mediaModel->insertMedia($data);

        // Generate direct URL for BBCode
        $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $filename);
        // Lấy URL tuỳ theo chế độ Cloudflare hay Direct
        if ($this->mediaConfig['telegram_upload_mode'] === 'direct') {
            $url = url('/media/download/' . $filecate . '/' . urlencode($filename));
        } else {
            $worker = !empty($this->mediaConfig['cloudflare_worker_url']) ? rtrim($this->mediaConfig['cloudflare_worker_url'], '/') : 'https://nosineup.stockage.workers.dev';
            $url = $worker . '/download/' . $filecate . '/' . urlencode($filename);
        }

        return [
            'status' => 'success',
            'url' => $url,
            'is_image' => $isImage,
            'file_id' => $filecate,
            'message' => 'Tải lên thành công!'
        ];
    }

    public function library()
    {
        $author = $this->request->user()->user['nick'];
        $files = $this->mediaModel->getLibrary($author, 50);

        $result = [];
        foreach ($files as $f) {
            $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f['filename']);

            if ($this->mediaConfig['telegram_upload_mode'] === 'direct') {
                $url = url('/media/download/' . $f['filecate'] . '/' . urlencode($f['filename']));
            } else {
                $worker = !empty($this->mediaConfig['cloudflare_worker_url']) ? rtrim($this->mediaConfig['cloudflare_worker_url'], '/') : 'https://nosineup.stockage.workers.dev';
                $url = $worker . '/download/' . $f['filecate'] . '/' . urlencode($f['filename']);
            }

            $result[] = [
                'id' => $f['id'],
                'filename' => $f['filename'],
                'filesize' => $f['filesize'],
                'url' => $url,
                'is_image' => $isImage,
                'time' => date('d/m/Y H:i', $f['time'])
            ];
        }

        return ['status' => 'success', 'data' => $result];
    }

    public function telegramDownload($file_id, $file_name)
    {
        if ($this->mediaConfig['telegram_upload_mode'] !== 'direct') {
            die('Chế độ direct bị vô hiệu hoá');
        }

        $token = $this->mediaConfig['telegram_bot_token'];

        // 1. Lấy file_path
        $apiUrl = "https://api.telegram.org/bot{$token}/getFile?file_id={$file_id}";
        $response = file_get_contents($apiUrl);
        if (!$response) {
            http_response_code(404);
            die('File not found');
        }

        $data = json_decode($response, true);
        if (!isset($data['result']['file_path'])) {
            http_response_code(404);
            die('File not found in Telegram');
        }

        $filePath = $data['result']['file_path'];
        $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";

        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed'
        ];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';

        // 2. Stream file
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($file_name) . '"');
        header('Access-Control-Allow-Origin: *');

        // Lưu cache 30 ngày (2592000 giây)
        $cacheSeconds = 2592000;
        header('Cache-Control: public, max-age=' . $cacheSeconds);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheSeconds) . ' GMT');
        header('Pragma: cache');

        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_exec($ch);
        curl_close($ch);
        exit;
    }
}
