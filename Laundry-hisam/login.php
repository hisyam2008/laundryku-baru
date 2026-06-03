<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['user'])) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/Database.php';
    try {
        $db   = (new Database())->connect();
        $stmt = $db->prepare("SELECT * FROM tb_user WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $u = $stmt->fetch();

        if ($u && ($u['password'] === $_POST['password'] || password_verify($_POST['password'], $u['password']))) {
            $_SESSION['user']    = $u['username'];
            $_SESSION['nama']    = $u['nama'];
            $_SESSION['role']    = $u['role'];
            $_SESSION['id_user']   = $u['id'];
            $_SESSION['id_outlet'] = $u['id_outlet'];
            header('Location: index.php');
            exit;
        }
        $error = 'Username atau password salah.';
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Laundry Hisam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background: #1a202c; /* Deep Slate */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #edf2f7;
        }
        .login-card {
            background: #2d3748; /* Soft Dark Card */
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            border: 1px solid #4a5568;
        }
        .btn-primary {
            background: linear-gradient(135deg, #63b3ed 0%, #3182ce 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.2s;
            color: #fff;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);
            color: #fff;
        }
        .form-control {
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #4a5568;
            background-color: #1a202c;
            color: #fff;
        }
        .form-control:focus {
            border-color: #63b3ed;
            box-shadow: 0 0 0 3px rgba(99, 179, 237, 0.2);
            background-color: #1a202c;
            color: #fff;
        }
        .form-control::placeholder {
            color: #718096;
        }
    </style>

</head>
<body class="p-3">
    <div class="login-card">
        <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">Laundry Hisam</h4>
            <p class="text-white-50 small">Welcome back! Please login to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small py-2 mb-4" role="alert">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 shadow-sm">Sign In</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
