<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PriceSmart</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-dark: #0a0b10;
            --bg-card: rgba(17, 24, 39, 0.7);
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.5);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-gradient: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            overflow: hidden;
            background-image: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
        }

        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .logo {
            font-family: 'Outfit', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        h2 { margin-bottom: 0.5rem; font-family: 'Outfit'; }
        p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 2rem; }

        .form-group {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            color: var(--text-muted);
            width: 18px;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.8rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            outline: none;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 15px var(--primary-glow);
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: var(--accent-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 4px 14px var(--primary-glow);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--primary-glow);
        }

        .login-footer {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .login-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 2rem;
            }
        }

        @media (max-height: 700px) {
            body {
                height: auto;
                min-height: 100vh;
                padding: 2rem 1rem;
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo">
            <i data-lucide="zap"></i> PriceSmart
        </div>
        <h2>Bem-vindo de volta!</h2>
        <p>Acesse seu painel administrativo para gerenciar seus lucros.</p>

        <?php if (isset($_GET['error'])): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-size: 0.85rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                E-mail ou senha inválidos.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; font-size: 0.85rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                Conta criada com sucesso! Faça login.
            </div>
        <?php endif; ?>

        <form action="auth_handler.php?action=login" method="POST">
            <div class="form-group">
                <label>E-mail</label>
                <div class="input-wrapper">
                    <i data-lucide="mail"></i>
                    <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
                </div>
            </div>

            <div class="form-group">
                <label>Senha</label>
                <div class="input-wrapper">
                    <i data-lucide="lock"></i>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: 1.5rem;">
                <a href="#" style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none;">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn-login">Entrar no Painel</button>
        </form>

        <div class="login-footer">
            Não tem uma conta? <a href="register.php">Criar agora</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
