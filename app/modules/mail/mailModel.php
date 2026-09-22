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
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count_mail FROM mail WHERE sender_receiver = :sender_receiver
        ');
        $stmt->bindParam(':sender_receiver', $Sender_Receiver, \PDO::PARAM_STR);
        $stmt->execute();
        $counts = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats = $counts['count_mail'];
        return $stats;
    }

    public function MailList($MyDetail)
    {
        $result = [];
        if ($MyDetail['mail_list'] != '') {
            $getMailList = explode('.', $MyDetail['mail_list']);
            $sysBot = defined('UserBot') ? UserBot : 'sei';
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

        $stmt = $this->db->prepare('
            SELECT * FROM mail WHERE sender_receiver = :sender_receiver ORDER BY id DESC LIMIT 1
        ');
        $stmt->bindParam(':sender_receiver', $Sender_Receiver, \PDO::PARAM_STR);
        $stmt->execute();
        $output = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $output;
    }

    public function MailDetailList($Sender, $Receiver, $per = 10, $start = 0)
    {
        $Sender = mb_strtolower($Sender);
        $Receiver = mb_strtolower($Receiver);
        $Sender_Receiver = $Sender . '_' . $Receiver;

        $stmt = $this->db->prepare('
            SELECT * FROM mail WHERE sender_receiver = :sender_receiver ORDER BY id DESC LIMIT :start, :per
        ');
        $stmt->bindParam(':sender_receiver', $Sender_Receiver, \PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':per', $per, \PDO::PARAM_INT);
        $stmt->execute();
        $output = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $output;
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
            # thêm vào danh sách hộp thư nếu chưa có
            $stmt = $this->db->prepare("UPDATE `users` SET `mail_list` = ? WHERE `nick` = ?");
            $stmt->execute([$Receiver . '.' . $SenderDetail['mail_list'], $Sender]);
        } else {
            # cập nhật lên danh sách đầu
            $updated_mail_list = $Receiver . '.' . str_replace($Receiver . '.', '', $SenderDetail['mail_list']);
            $stmt = $this->db->prepare("UPDATE `users` SET `mail_list` = ? WHERE `nick` = ?");
            $stmt->execute([$updated_mail_list, $Sender]);
        }
        # lưu dữ liệu vào bảng `mail`
        $stmt_InstertToSender = $this->db->prepare('INSERT INTO `mail` SET
            `sender_receiver` = :sender_receiver,
            `nick` = :nick,
            `content` = :content,
            `time` = :time,
            `view` = :view
        ');
        $stmt_InstertToSender->execute([
            'sender_receiver' => $Sender_Receiver,
            'nick' => $Sender,
            'content' => $content,
            'time' => TIME,
            'view' => 'yes'
        ]);

        // lưu vào người nhận
        # thêm thông báo tin nhắn mới cho người nhận
        $stmt = $this->db->prepare("UPDATE `users` SET `new_mail` = ? WHERE `nick` =?");
        $stmt->execute([$Sender . '.' . $ReceiverDetail['new_mail'], $Receiver]);
        # thêm vào danh sách tin nhắn
        if (!in_array($Sender, $ReceiverMailList)) {
            # thêm vào danh sách hộp thư nếu chưa có
            $stmt = $this->db->prepare("UPDATE `users` SET `mail_list` = ? WHERE `nick` = ?");
            $stmt->execute([$Sender . '.' . $ReceiverDetail['mail_list'], $Receiver]);
        } else {
            # cập nhật lên danh sách đầu
            $updated_mail_list = $Sender . '.' . str_replace($Sender . '.', '', $ReceiverDetail['mail_list']);
            $stmt = $this->db->prepare("UPDATE `users` SET `mail_list` = ? WHERE `nick` = ?");
            $stmt->execute([$updated_mail_list, $Receiver]);
        }
        $stmt_InstertToReceiver = $this->db->prepare('INSERT INTO `mail` SET
            `sender_receiver` = :sender_receiver,
            `nick` = :nick,
            `content` = :content,
            `time` = :time,
            `view` = :view
        ');
        $stmt_InstertToSender->execute([
            'sender_receiver' => $Receiver_Sender,
            'nick' => $Sender,
            'content' => _e($content),
            'time' => TIME,
            'view' => 'no'
        ]);
    }

    public function MailViewUpdate($mail_id)
    {
        $UserModel = $this->load->model('user');
        // lấy thông tin hàng hiện tại
        $stmt = $this->db->prepare('
            SELECT * FROM mail WHERE id = :id ORDER BY id DESC LIMIT 1
        ');
        $stmt->execute(['id' => $mail_id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $Sender_Receiver = $data['sender_receiver'];
        $SR = explode('_', $Sender_Receiver);
        $Sender = $SR[0];
        $Receiver = $SR[1];
        $SenderDetail = $UserModel->UserDetailWithFields('nick', $Sender);
        // xóa tin nhắn mới
        $SenderNewMail = str_replace($Receiver . '.', '', $SenderDetail['new_mail']);
        $stmt_update_user = $this->db->prepare('UPDATE `users` SET `new_mail` = :SenderNewMail WHERE `nick` = :Sender');
        $stmt_update_user->execute(['SenderNewMail' => $SenderNewMail, 'Sender' => $Sender]);
        // cập nhật trong bảng `mail`
        $stmt_update_mail = $this->db->prepare('UPDATE `mail` SET `view` = :view WHERE `id` = :id');
        $stmt_update_mail->execute([
            'view' => 'yes',
            'id' => $mail_id
        ]);
        // cập nhật cột `new_mail` trong bảng `users`
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
        $stmt = $this->db->prepare('UPDATE `users` SET `blocklist` = :SenderBlockListSave WHERE `nick` = :Sender');
        $stmt->execute(['SenderBlockListSave' => $SenderBlockListSave, 'Sender' => $SenderDetail['nick']]);

        return null;
    }

    /* =============== TIN NHẮN HỆ THỐNG =============== */
    // đếm
    public function MailSystemCount($MyDetail)
    {
        $system_nick =  $MyDetail['nick'] . '_' . UserBot;
        $stmt = $this->db->prepare('
            SELECT COUNT(*) as count FROM `mail` WHERE `sender_receiver` = :sender_receiver
        ');
        $stmt->bindParam(':sender_receiver', $system_nick, \PDO::PARAM_STR);
        $stmt->execute();
        $counts = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $counts['count'];
    }
    // danh sách
    public function MailSystemList($MyDetail, $per, $start)
    {
        $system_nick =  $MyDetail['nick'] . '_' . UserBot;
        $stmt = $this->db->prepare('
            SELECT * FROM `mail` WHERE `sender_receiver` = :sender_receiver
            ORDER BY `time` DESC LIMIT :start, :per
        ');
        $stmt->bindParam(':sender_receiver', $system_nick, \PDO::PARAM_STR);
        $stmt->bindParam(':start', $start, \PDO::PARAM_INT);
        $stmt->bindParam(':per', $per, \PDO::PARAM_INT);
        $stmt->execute();
        $output = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $output;
    }
    // đánh dấu tất cả là đã đọc
    public function MailSystemView($MyDetail)
    {
        $stmt = $this->db->prepare('
            UPDATE `mail` SET `view` = :yes_view 
            WHERE `sender_receiver` = :sender_receiver AND view = :no_view
        ');
        $stmt->execute([
            'yes_view' => 'yes',
            'no_view' => 'no',
            'sender_receiver' => $MyDetail['nick'] . '_' . UserBot
        ]);
        return null;
    }
    // xóa tất cả
    public function MailSystemClear($MyDetail)
    {
        $stmt = $this->db->prepare('DELETE FROM `mail` WHERE `sender_receiver` = :sender_receiver');
        $stmt->execute(['sender_receiver' => $MyDetail['nick'] . '_' . UserBot]);
        return null;
    }
}
