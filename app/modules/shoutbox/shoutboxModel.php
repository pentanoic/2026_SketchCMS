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

class shoutboxModel extends Model
{
    // các chức năng
    public function ChatSend($user, $msg)
    {
        $stmt = $this->db->prepare('INSERT INTO `chat` SET
            `name` = :name,
            `time` = :time,
            `comment` = :comment
        ');
        $stmt->execute([
            'name' => $user['nick'],
            'time' => TIME,
            'comment' => $msg
        ]);

        // notification logic cho quote
        preg_match_all('/@\[module=([a-zA-Z0-9_]+);quote=(\d+)\]/', $msg, $matches, PREG_SET_ORDER);
        $notified_users = [];
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
                        $quoteUrl = '[url=/articles/' . $row['blogid'] . '.html]bình luận[/url]';
                    }
                }

                if ($quoteAuthorNick && $quoteAuthorNick !== $user['nick'] && !in_array($quoteAuthorNick, $notified_users)) {
                    $notified_users[] = $quoteAuthorNick;
                    $system_notify_content = '@' . $user['nick'] . ' đã trích dẫn tin nhắn của bạn trong ' . $quoteUrl;

                    // UserBot is defined globally, if not we fallback to "Hệ Thống"
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
                        'nick' => $user['nick'],
                        'content' => $system_notify_content,
                        'time' => TIME,
                        'view' => 'no'
                    ]);
                }
            } catch (Exception $e) {
            }
        }

        return null;
    }

    // lấy thông tin
    public function ChatDetail($id)
    {
        $stmt = $this->db->prepare('SELECT * FROM `chat` WHERE `id` = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetchAll()[0];
        return $data;
    }

    public function ChatList($start, $limit)
    {
        // SQL query to fetch chat details if available
        $sql = '
            SELECT * FROM chat ORDER BY id desc LIMIT :start, :limit
        ';
        $start = abs((int)$start);
        $limit = abs((int)$limit);
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        // Execute the query
        $stmt->execute();
        // Fetch all results
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
}
