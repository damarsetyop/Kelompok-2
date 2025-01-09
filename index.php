<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index - Web Server</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            color: #4facfe;
        }

        input, textarea, button {
            width: 100%;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            background: #4facfe;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #00c6ff;
        }

        a {
            color: #4facfe;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php
// Konfigurasi Database
$host = 'localhost';
$dbname = 'myapp';
$user = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

session_start(); // Memulai session

// Fungsi Registrasi
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Mengamankan password

    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    if ($stmt->execute([$username, $password])) {
        echo "<script>alert('Registrasi berhasil! Silakan login.');</script>";
    } else {
        echo "<script>alert('Gagal registrasi. Username mungkin sudah digunakan.');</script>";
    }
}

// Fungsi Login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id']; // Menyimpan ID user di session
        header("Location: index.php"); // Redirect setelah login berhasil
        exit;
    } else {
        echo "<script>alert('Login gagal. Username atau password salah.');</script>";
    }
}

// Fungsi Input Data dan Upload File
if (isset($_POST['submit_data'])) {
    // Pastikan user sudah login
    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('Silakan login terlebih dahulu.');</script>";
        exit;
    }

    $content = $_POST['content'];
    $user_id = $_SESSION['user_id'];
    $file_path = null;

    // Proses upload file
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Buat folder uploads jika belum ada
        }

        $file_name = basename($_FILES['file']['name']);
        $target_file = $target_dir . time() . "_" . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
            $file_path = $target_file;
        } else {
            echo "<script>alert('Gagal mengunggah file.');</script>";
        }
    }

    // Menyimpan data ke database
    $stmt = $pdo->prepare("INSERT INTO data (user_id, content, file_path) VALUES (?, ?, ?)");
    if ($stmt->execute([$user_id, $content, $file_path])) {
        echo "<script>alert('Data berhasil disimpan!');</script>";
    } else {
        echo "<script>alert('Gagal menyimpan data.');</script>";
    }
}

// Fungsi Logout
if (isset($_GET['logout'])) {
    session_destroy(); // Hapus session
    unset($_SESSION['user_id']); // Hapus data user
    header("Location: index.php"); // Arahkan ke halaman utama setelah logout
    exit;
}
?>

<div class="container">
    <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Form Login -->
        <h1>Login / Register</h1>
        <form method="POST">
            <h3>Login</h3>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <p>Belum punya akun? <a href="#register_form" onclick="document.getElementById('register_form').style.display='block';">Daftar</a></p>

        <!-- Form Register -->
        <div id="register_form" style="display: none;">
            <form method="POST">
                <h3>Register</h3>
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="register">Register</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Form Input Data -->
        <h1>Input Data</h1>
        <form method="POST" enctype="multipart/form-data">
            <textarea name="content" placeholder="Masukkan data Anda..." required></textarea>
            <input type="file" name="file" accept="image/*,application/pdf">
            <button type="submit" name="submit_data">Simpan</button>
        </form>
        <a href="?logout=true">Logout</a>
    <?php endif; ?>
</div>
</body>
</html>
