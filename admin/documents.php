<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$db = get_db();
$tableError = '';
$dbErrorMsg = 'Erro na base de dados. Verifique se a tabela "documents" existe. Execute install.php ou importe database/schema.sql.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $docId = (int)($_POST['doc_id'] ?? 0);

    try {
        if ($action === 'toggle_active' && $docId) {
            $current = $db->prepare("SELECT active FROM documents WHERE id = ?");
            $current->execute([$docId]);
            $row = $current->fetch();
            if ($row) {
                $db->prepare("UPDATE documents SET active = ? WHERE id = ?")->execute([$row['active'] ? 0 : 1, $docId]);
            }
        } elseif ($action === 'delete' && $docId) {
            $db->prepare("DELETE FROM documents WHERE id = ?")->execute([$docId]);
        }
    } catch (PDOException $e) {
        $tableError = $dbErrorMsg;
    }
    if (empty($tableError)) {
        redirect('/admin/documents.php');
    }
}

try {
    $documents = $db->query("SELECT * FROM documents ORDER BY sort_order ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $documents = [];
    $tableError = $dbErrorMsg;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos | Admin Solicitador</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="admin-main">
        <header class="admin-header">
            <h1>Documentos</h1>
            <a href="/admin/document-edit.php" class="btn btn-gold">+ Novo Documento</a>
        </header>

        <?php if (!empty($tableError)): ?>
        <div class="admin-card"><div class="alert alert-error"><?= htmlspecialchars($tableError) ?></div></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Título (PT)</th>
                            <th>Ficheiro</th>
                            <th>Ordem</th>
                            <th>Data</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                        <tr>
                            <td><?= (int)$doc['id'] ?></td>
                            <td><?= sanitize($doc['title_pt']) ?></td>
                            <td><a href="<?= sanitize($doc['file_url']) ?>" target="_blank"><?= sanitize($doc['original_filename']) ?></a></td>
                            <td><?= (int)$doc['sort_order'] ?></td>
                            <td><?= sanitize(substr($doc['created_at'], 0, 10)) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="doc_id" value="<?= (int)$doc['id'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $doc['active'] ? 'btn-success' : 'btn-muted' ?>">
                                        <?= $doc['active'] ? 'Ativo' : 'Inativo' ?>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <a href="/admin/document-edit.php?id=<?= (int)$doc['id'] ?>" class="btn btn-sm">Editar</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Eliminar este documento?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="doc_id" value="<?= (int)$doc['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($documents)): ?>
                        <tr><td colspan="7" class="text-center">Sem documentos. Adicione o primeiro documento.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>
