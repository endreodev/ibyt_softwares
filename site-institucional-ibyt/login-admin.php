<?php
session_start();

// Verificar se já está logado
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin-loja.php');
    exit();
}

// Processar login
$error_message = '';
$success_message = '';

// Verificar se foi logout
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $success_message = 'Logout realizado com sucesso!';
}

// Verificar se a sessão expirou
if (isset($_GET['expired']) && $_GET['expired'] == '1') {
    $error_message = 'Sua sessão expirou. Faça login novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Credenciais administrativas
    if ($username === 'admin' && $password === 'Samurai@12') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = 'admin';
        $_SESSION['login_time'] = time();
        
        // Redirecionar para admin
        header('Location: admin-loja.php');
        exit();
    } else {
        $error_message = 'Usuário ou senha incorretos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrativo - IBYT Store</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .login-logo img {
            height: 50px;
            width: auto;
        }

        .login-logo-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #007bff;
            background: white;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.25rem;
            margin-top: 0.75rem;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 1.25rem;
            margin-top: 0.75rem;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #007bff;
        }

        .login-btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #f5c6cb;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .back-link a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
                margin: 1rem;
            }

            .login-logo-text {
                font-size: 1.5rem;
            }

            .login-title {
                font-size: 1.25rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <img src="assets/img/logo.png" alt="IBYT Software">
                <span class="login-logo-text">IBYT Store</span>
            </div>
            <h1 class="login-title">Acesso Administrativo</h1>
            <p class="login-subtitle">Faça login para gerenciar a loja</p>
        </div>

        <?php if ($success_message): ?>
            <div class="success-message">
                <span class="material-icons-round">check_circle</span>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message">
                <span class="material-icons-round">error</span>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="username" class="form-label">Usuário</label>
                <div style="position: relative;">
                    <span class="material-icons-round input-icon">person</span>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Digite seu usuário"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        required
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Senha</label>
                <div style="position: relative;">
                    <span class="material-icons-round input-icon">lock</span>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Digite sua senha"
                        required
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <span class="material-icons-round" id="password-icon">visibility</span>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <span class="material-icons-round">login</span>
                Entrar
            </button>
        </form>

        <div class="back-link">
            <a href="loja.php">
                <span class="material-icons-round">arrow_back</span>
                Voltar à loja
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = 'visibility';
            }
        }

        // Auto-focus no primeiro campo
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Enter para submeter o form
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.login-form').submit();
            }
        });
    </script>
</body>
</html>
