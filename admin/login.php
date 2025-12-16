<?php
session_start();

// Check if already logged in as admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Hardcoded admin credentials
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'admin';
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — Voyage en Route</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="../assets/css/styles.css">
    
    <style>
      body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }
      
      .admin-login-card {
        max-width: 500px;
        width: 100%;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
        border-radius: 15px;
      }
    </style>
  </head>
  <body>
    <div class="container-fluid d-flex align-items-center justify-content-center" style="min-height: 100vh;">
      <div class="row justify-content-center w-100">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4">
          <div class="card shadow-lg admin-login-card border-0">
            <div class="card-body p-5">
              <div class="text-center mb-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                  <i class="fas fa-user-shield fa-3x"></i>
                </div>
                <h2 class="fw-bold">Admin Portal</h2>
                <p class="text-muted">Voyage en Route Management</p>
              </div>

              <?php if ($error): ?>
              <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>
              <?php endif; ?>

              <form method="POST" action="login.php">
                <div class="mb-4">
                  <label for="username" class="form-label fw-bold">
                    <i class="fas fa-user me-2"></i>Username
                  </label>
                  <input 
                    type="text" 
                    class="form-control form-control-lg" 
                    id="username" 
                    name="username" 
                    placeholder="Enter admin username"
                    required
                    autofocus
                  >
                </div>

                <div class="mb-4">
                  <label for="password" class="form-label fw-bold">
                    <i class="fas fa-lock me-2"></i>Password
                  </label>
                  <input 
                    type="password" 
                    class="form-control form-control-lg" 
                    id="password" 
                    name="password" 
                    placeholder="Enter admin password"
                    required
                  >
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; font-weight: 600;">
                  <i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard
                </button>
              </form>

              <hr class="my-4">

              <div class="text-center">
                <a href="../home.html" class="text-decoration-none">
                  <i class="fas fa-arrow-left me-2"></i>Back to Website
                </a>
              </div>

              <div class="text-center mt-3">
                <small class="text-muted">
                  <i class="fas fa-shield-alt me-1"></i>Authorized Personnel Only
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>