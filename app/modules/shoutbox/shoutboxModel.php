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
        db('chat')->insert([
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
                    if ($row = db('chat')->select(['name'])->where('id', $quoteId)->first()) {
                        $quoteAuthorNick = $row['name'];
                        $quoteUrl = '[url=/shoutbox]trò chuyện[/url]';
                    }
                } elseif ($module === 'articles') {
                    if ($row = db('articles_cmt')->select(['author', 'blogid'])->where('id', $quoteId)->first()) {
                        $quoteAuthorNick = $row['author'];
                        $quoteUrl = '[url=/articles/' . $row['blogid'] . '.html]bình luận[/url]';
                    }
                }

                if ($quoteAuthorNick && $quoteAuthorNick !== $user['nick'] && !in_array($quoteAuthorNick, $notified_users)) {
                    $notified_users[] = $quoteAuthorNick;
                    $system_notify_content = '@' . $user['nick'] . ' đã trích dẫn tin nhắn của bạn trong ' . $quoteUrl;

                    $bot_name = (string) Env::get('USER_BOT', 'SystemBOT');

                    db('mail')->insert([
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
        return db('chat')->where('id', $id)->first();
    }

    public function ChatList($start, $limit)
    {
        return db('chat')
            ->orderBy('id', 'DESC')
            ->limit(abs((int)$limit))
            ->offset(abs((int)$start))
            ->get();
    }
}
