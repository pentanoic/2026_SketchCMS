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
        return db('articles_file')->insert([
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
    }

    public function getLibrary($author, $limit = 50)
    {
        return db('articles_file')
            ->where('author', $author)
            ->orderBy('id', 'DESC')
            ->limit((int)$limit)
            ->get();
    }
}
