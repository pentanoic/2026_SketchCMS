<?php

return function (TestRunner $t, string $root): void {
    $pdo = Database::pdo();
    $pdo->exec('CREATE TEMPORARY TABLE IF NOT EXISTS _test_items (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, qty INTEGER DEFAULT 0)');
    $pdo->exec('DELETE FROM _test_items');

    $id1 = db('_test_items')->insert(['name' => 'Alpha', 'qty' => 10]);
    $id2 = db('_test_items')->insert(['name' => 'Beta', 'qty' => 20]);
    $t->assertTrue($id1 > 0 && $id2 > $id1, 'insert() returns incrementing IDs');

    $all = db('_test_items')->orderBy('id', 'ASC')->get();
    $t->assertEquals(2, count($all), 'get() returns 2 rows');

    $first = db('_test_items')->where('name', 'Alpha')->first();
    $t->assertEquals('Alpha', $first['name'] ?? null, 'where()->first() finds matching row');

    $orRows = db('_test_items')->where('name', 'Alpha')->orWhere('name', 'Beta')->get();
    $t->assertEquals(2, count($orRows), 'orWhere() matches both rows');

    $rawRows = db('_test_items')->selectRaw('id, UPPER(name) AS uname')->whereRaw('qty > ?', [15])->get();
    $t->assertEquals('BETA', $rawRows[0]['uname'] ?? null, 'selectRaw() + whereRaw() works');

    db('_test_items')->where('id', $id1)->increment('qty', 5);
    $row1 = db('_test_items')->where('id', $id1)->first();
    $t->assertEquals(15, (int)($row1['qty'] ?? 0), 'increment() increases qty');

    db('_test_items')->where('id', $id1)->decrement('qty', 3);
    $row1b = db('_test_items')->where('id', $id1)->first();
    $t->assertEquals(12, (int)($row1b['qty'] ?? 0), 'decrement() decreases qty');

    db('_test_items')->where('id', $id2)->update(['name' => 'Beta2']);
    $row2 = db('_test_items')->where('id', $id2)->first();
    $t->assertEquals('Beta2', $row2['name'] ?? null, 'update() modifies row');

    $deleted = db('_test_items')->where('id', $id2)->delete();
    $t->assertEquals(1, $deleted, 'delete() removes 1 row');
    $t->assertEquals(1, db('_test_items')->count(), 'count() returns 1 after delete');

    $pdo->exec('DROP TABLE IF EXISTS _test_items');
};
