<?php
require __DIR__ . '/../includes/bootstrap.php';

if (Auth::check()) {
    redirect('index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    // Simple brute-force slow-down: after 5 failures, wait 30 seconds
    $locked = ($_SESSION['login_fail'] ?? 0) >= 5 && (time() - ($_SESSION['login_last'] ?? 0)) < 30;

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } elseif ($locked) {
        $error = 'Too many failed attempts. Please wait 30 seconds and try again.';
    } else {
        try {
            if (Auth::attempt($username, $password)) {
                unset($_SESSION['login_fail'], $_SESSION['login_last']);
                flash('success', 'Welcome, ' . Auth::user()['full_name'] . '!');
                redirect('index.php');
            }
            $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
            $_SESSION['login_last'] = time();
            $error = 'Incorrect username or password.';
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Login is temporarily unavailable. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Agriculture Information System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">Agriculture Information System</h4>
                </div>
                <div class="card-body p-4">
                    <h5 class="mb-3 text-center">Sign in</h5>
                    <?= flash_render() ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <form method="post" autocomplete="off">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control"
                                   value="<?= e($username) ?>" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
