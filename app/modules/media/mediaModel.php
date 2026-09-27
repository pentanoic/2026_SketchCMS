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

class mediaModel extends Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function insertMedia($data)
    {
        $stmt = $this->db->prepare("INSERT INTO `articles_file` (`time`, `filename`, `filecate`, `filesize`, `type`, `author`, `status`, `price`, `blogid`) VALUES (:time, :filename, :filecate, :filesize, :type, :author, :status, :price, :blogid)");
        $stmt->execute([
            'time' => $data['time'],
            'filename' => $data['filename'],
            'filecate' => $data['filecate'],
            'filesize' => $data['filesize'],
            'type' => $data['type'],
            'author' => $data['author'],
            'status' => $data['status'],
            'price' => $data['price'],
            'blogid' => isset($data['blogid']) ? (int)$data['blogid'] : 0
        ]);
        return $this->db->lastInsertId();
    }

    public function getLibrary($author, $limit = 50)
    {
        $stmt = $this->db->prepare("SELECT * FROM `articles_file` WHERE `author` = :author ORDER BY `id` DESC LIMIT :limit");
        $stmt->bindValue(':author', $author, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
