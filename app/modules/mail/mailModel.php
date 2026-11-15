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

class mailModel extends Model
{
    /* =============== TIN NHẮN RIÊNG =============== */
    public function MailCount($Sender, $Receiver)
    {
        $Sender = mb_strtolower($Sender);
        $Receiver = mb_strtolower($Receiver);
        $Sender_Receiver = $Sender . '_' . $Receiver;
        return db('mail')->where('sender_receiver', $Sender_Receiver)->count();
    }

    public function MailList($MyDetail)
    {
        $result = [];
        if ($MyDetail['mail_list'] != '') {
            $getMailList = explode('.', $MyDetail['mail_list']);
            $sysBot = (string) Env::get('USER_BOT', 'SystemBOT');
            $getMailList = array_filter($getMailList, function ($value) use ($sysBot) {
                return $value !== null && $value !== false && $value !== "" && $value !== $sysBot;
            });
            $result = array_values($getMailList);
        }
        return $result;
    }

    public function LatestMailDetail($Sender, $Receiver)
    {
        $Sender = mb_strtolower($Sender);
        $Receiver = mb_strtolower($Receiver);
        $Sender_Receiver = $Sender . '_' . $Receiver;

        return db('mail')
            ->where('sender_receiver', $Sender_Receiver)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function MailDetailList($Sender, $Receiver, $per = 10, $start = 0)
    {
        $Sender = mb_strtolower($Sender);
        $Receiver = mb_strtolower($Receiver);
        $Sender_Receiver = $Sender . '_' . $Receiver;

        return db('mail')
            ->where('sender_receiver', $Sender_Receiver)
            ->orderBy('id', 'DESC')
            ->limit((int)$per)
            ->offset((int)$start)
            ->get();
    }

    public function MailSend($SenderDetail, $ReceiverDetail, $content)
    {
        $Sender = mb_strtolower($SenderDetail['nick']);
        $Receiver = mb_strtolower($ReceiverDetail['nick']);
        $Sender_Receiver = $Sender . '_' . $Receiver;
        $Receiver_Sender = $Receiver . '_' . $Sender;
        $SenderMailList = $this->MailList($SenderDetail);
        $ReceiverMailList = $this->MailList($ReceiverDetail);

        // lưu vào người gửi
        if (!in_array($Receiver, $SenderMailList)) {
            db('users')->where('nick', $Sender)->update([
                'mail_list' => $Receiver . '.' . $SenderDetail['mail_list']
            ]);
        } else {
            $updated_mail_list = $Receiver . '.' . str_replace($Receiver . '.', '', $SenderDetail['mail_list']);
            db('users')->where('nick', $Sender)->update([
                'mail_list' => $updated_mail_list
            ]);
        }
        db('mail')->insert([
            'sender_receiver' => $Sender_Receiver,
            'nick' => $Sender,
            'content' => $content,
            'time' => TIME,
            'view' => 'no'
        ]);

        // lưu vào người nhận
        db('users')->where('nick', $Receiver)->update([
            'new_mail' => $Sender . '.' . $ReceiverDetail['new_mail']
        ]);
        if (!in_array($Sender, $ReceiverMailList)) {
            db('users')->where('nick', $Receiver)->update([
                'mail_list' => $Sender . '.' . $ReceiverDetail['mail_list']
            ]);
        } else {
            $updated_mail_list = $Sender . '.' . str_replace($Sender . '.', '', $ReceiverDetail['mail_list']);
            db('users')->where('nick', $Receiver)->update([
                'mail_list' => $updated_mail_list
            ]);
        }
        db('mail')->insert([
            'sender_receiver' => $Receiver_Sender,
            'nick' => $Sender,
            'content' => $content,
            'time' => TIME,
            'view' => 'no'
        ]);
    }

    public function MailViewUpdate($mail_id, $viewerNick = null)
    {
        $UserModel = $this->load->model('user');
        // lấy thông tin hàng hiện tại
        $data = db('mail')->where('id', $mail_id)->orderBy('id', 'DESC')->first();
        if (!$data || empty($data['sender_receiver'])) {
            return null;
        }
        $Sender_Receiver = $data['sender_receiver'];
        $SR = explode('_', $Sender_Receiver);
        $Sender = $SR[0] ?? '';
        $Receiver = $SR[1] ?? '';
        if ($viewerNick !== null && mb_strtolower($viewerNick) !== mb_strtolower($Sender)) {
            return null;
        }
        $SenderDetail = $UserModel->UserDetailWithFields('nick', $Sender);
        if (!$SenderDetail) {
            return null;
        }
        // xóa tin nhắn mới
        $SenderNewMail = str_replace($Receiver . '.', '', $SenderDetail['new_mail']);
        db('users')->where('nick', $Sender)->update(['new_mail' => $SenderNewMail]);
        // cập nhật trong bảng `mail`
        db('mail')->where('id', (int)$mail_id)->update(['view' => 'yes']);

        // đồng bộ trạng thái đã xem sang bản sao của người gửi
        if ($Sender !== '' && $Receiver !== '') {
            db('mail')
                ->where('sender_receiver', $Receiver . '_' . $Sender)
                ->where('nick', $data['nick'])
                ->where('time', $data['time'])
                ->update(['view' => 'yes']);
        }
        return null;
    }

    public function MailBlocked($SenderDetail, $ReceiverDetail)
    {
        $SenderBlockList = explode('.', $SenderDetail['blocklist']);
        if (in_array($ReceiverDetail['nick'], $SenderBlockList)) {
            $SenderBlockListSave = str_replace($ReceiverDetail['nick'] . '.', '', $SenderDetail['blocklist']);
        } else {
            $SenderBlockListSave = $ReceiverDetail['nick'] . '.' . $SenderDetail['blocklist'];
        }
        db('users')->where('nick', $SenderDetail['nick'])->update(['blocklist' => $SenderBlockListSave]);

        return null;
    }

    /* =============== TIN NHẮN HỆ THỐNG =============== */
    // đếm
    public function MailSystemCount($MyDetail)
    {
        $system_nick = $MyDetail['nick'] . '_' . (string) Env::get('USER_BOT', 'SystemBOT');
        return db('mail')->where('sender_receiver', $system_nick)->count();
    }
    // danh sách
    public function MailSystemList($MyDetail, $per, $start)
    {
        $system_nick = $MyDetail['nick'] . '_' . (string) Env::get('USER_BOT', 'SystemBOT');
        return db('mail')
            ->where('sender_receiver', $system_nick)
            ->orderBy('time', 'DESC')
            ->limit((int)$per)
            ->offset((int)$start)
            ->get();
    }
    // đánh dấu tất cả là đã đọc
    public function MailSystemView($MyDetail)
    {
        $system_nick = $MyDetail['nick'] . '_' . (string) Env::get('USER_BOT', 'SystemBOT');
        db('mail')
            ->where('sender_receiver', $system_nick)
            ->where('view', 'no')
            ->update(['view' => 'yes']);
        return null;
    }
    // xóa tất cả
    public function MailSystemClear($MyDetail)
    {
        $system_nick = $MyDetail['nick'] . '_' . (string) Env::get('USER_BOT', 'SystemBOT');
        db('mail')->where('sender_receiver', $system_nick)->delete();
        return null;
    }
}
