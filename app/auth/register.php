<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | PriceSmart</title>
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
            min-height: 100vh;
            padding: 2rem 0;
            background-image: radial-gradient(circle at 50% 50%, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
        }

        .login-card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .full-width { grid-column: span 2; }

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
            box-sizing: border-box;
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
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo">
            <i data-lucide="zap"></i> PriceSmart
        </div>
        <h2>Crie sua conta</h2>
        <p>Comece a precificar de forma inteligente hoje mesmo.</p>

        <?php $plan = $_GET['plan'] ?? ''; ?>
        <form action="auth_handler.php?action=register" method="POST">
            <input type="hidden" name="selected_plan" value="<?= htmlspecialchars($plan) ?>">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nome Completo</label>
                    <div class="input-wrapper">
                        <i data-lucide="user"></i>
                        <input type="text" name="name" class="form-control" placeholder="Seu nome" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>Nome da Empresa</label>
                    <div class="input-wrapper">
                        <i data-lucide="building"></i>
                        <input type="text" name="company_name" class="form-control" placeholder="Ex: TecnoStore" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>CNPJ (Opcional)</label>
                    <div class="input-wrapper">
                        <i data-lucide="file-text"></i>
                        <input type="text" name="cnpj" id="cnpj" class="form-control" style="padding-left: 2.8rem;" placeholder="00.000.000/0001-00" maxlength="18">
                    </div>
                </div>

                <div class="form-group">
                    <label>Celular</label>
                    <div class="input-wrapper">
                        <i data-lucide="phone"></i>
                        <input type="text" name="phone" class="form-control" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label>E-mail Corporativo</label>
                    <div class="input-wrapper">
                        <i data-lucide="mail"></i>
                        <input type="email" name="email" class="form-control" placeholder="contato@empresa.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Senha</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock"></i>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirmar Senha</label>
                    <div class="input-wrapper">
                        <i data-lucide="shield-check"></i>
                        <input type="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-login">Criar Minha Conta</button>
        </form>

        <div class="login-footer">
            Já possui uma conta? <a href="login.php">Fazer Login</a>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function isValidCNPJ(cnpj) {
            cnpj = cnpj.replace(/[^\d]+/g, '');
            if (cnpj == '') return true; // Allow empty
            if (cnpj.length != 14) return false;

            // Eliminate known invalid CNPJs
            if (/^(\d)\1+$/.test(cnpj)) return false;

            // Validate DVs
            let size = cnpj.length - 2;
            let numbers = cnpj.substring(0, size);
            let digits = cnpj.substring(size);
            let sum = 0;
            let pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            let result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
            if (result != digits.charAt(0)) return false;

            size = size + 1;
            numbers = cnpj.substring(0, size);
            sum = 0;
            pos = size - 7;
            for (let i = size; i >= 1; i--) {
                sum += numbers.charAt(size - i) * pos--;
                if (pos < 2) pos = 9;
            }
            result = sum % 11 < 2 ? 0 : 11 - (sum % 11);
            if (result != digits.charAt(1)) return false;

            return true;
        }

        const regForm = document.querySelector('form');
        regForm.addEventListener('submit', (e) => {
            const cnpjVal = document.getElementById('cnpj').value;
            if (!isValidCNPJ(cnpjVal)) {
                e.preventDefault();
                alert('O CNPJ informado é inválido. Por favor, verifique os números.');
            }
        });

        function maskCNPJ(v) {
            v = v.replace(/\D/g, "");
            if (v.length > 14) v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, "$1.$2");
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
            v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
            v = v.replace(/(\d{4})(\d)/, "$1-$2");
            return v;
        }

        function maskPhone(v) {
            v = v.replace(/\D/g, "");
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 10) {
                v = v.replace(/^(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
            } else if (v.length > 5) {
                v = v.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
            } else if (v.length > 2) {
                v = v.replace(/^(\d{2})(\d{0,5})/, "($1) $2");
            } else if (v.length > 0) {
                v = v.replace(/^(\d{0,2})/, "($1");
            }
            return v;
        }

        const cnpjInput = document.getElementById('cnpj');
        if (cnpjInput) {
            cnpjInput.addEventListener('input', (e) => {
                e.target.value = maskCNPJ(e.target.value);
            });
        }

        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', (e) => {
                e.target.value = maskPhone(e.target.value);
            });
        }
    </script>
</body>
</html>
