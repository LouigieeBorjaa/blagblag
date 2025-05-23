<?php
require_once 'vendor/autoload.php';

use Aries\Dbmodel\Includes\Database;
use Aries\Dbmodel\Includes\Session;

$session = new Session();
$database = new Database();
$conn = $database->getConnection();

// Check if user is logged in
if(!$session->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$post = ['title' => '', 'content' => ''];
$error = '';
$success = '';

// If editing existing post
if(isset($_GET['edit'])) {
    $post_id = $_GET['edit'];
    $author_id = $session->getUserId();
    $sql = "SELECT * FROM posts WHERE id = :id AND author_id = :author_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $post_id);
    $stmt->bindParam(':author_id', $author_id);
    $stmt->execute();
    
    if($stmt->rowCount() == 1) {
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        header("Location: index.php");
        exit();
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $author_id = $session->getUserId();
    
    if(empty($title) || empty($content)) {
        $error = "All fields are required";
    } else {
        if(isset($_GET['edit'])) {
            // Update existing post
            $post_id = $_GET['edit'];
            $sql = "UPDATE posts SET title = :title, content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND author_id = :author_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':id', $post_id);
            $stmt->bindParam(':author_id', $author_id);
        } else {
            // Create new post
            $sql = "INSERT INTO posts (title, content, author_id) VALUES (:title, :content, :author_id)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':author_id', $author_id);
        }
        
        if($stmt->execute()) {
            $success = "Post " . (isset($_GET['edit']) ? "updated" : "created") . " successfully!";
            if(!isset($_GET['edit'])) {
                $post = ['title' => '', 'content' => ''];
            }
        } else {
            $error = "Error: " . implode(" ", $stmt->errorInfo());
        }
    }
}

// Regenerate session ID periodically
$session->regenerate();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['edit']) ? 'Edit Post' : 'Create New Post'; ?> - BlagStrike</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            text-decoration: none;
        }

        .main-content {
            flex: 1;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .editor-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        h1 {
            font-size: 1.8rem;
            color: #1a1a1a;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a4a4a;
            font-weight: 500;
        }

        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1.1rem;
            transition: all 0.2s;
        }

        textarea {
            width: 100%;
            min-height: 400px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            line-height: 1.6;
            resize: vertical;
            transition: all 0.2s;
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid #007bff;
            color: #007bff;
        }

        .btn-outline:hover {
            background-color: #007bff;
            color: white;
        }

        .error {
            background-color: #fff5f5;
            color: #dc3545;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #ffd7d7;
        }

        .success {
            background-color: #f0fff4;
            color: #28a745;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
        }

        .editor-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .editor-tips {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .editor-tips h3 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .editor-tips ul {
            list-style-position: inside;
            margin-left: 1rem;
        }

        .editor-tips li {
            margin-bottom: 0.25rem;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .main-content {
                padding: 0 1rem;
            }

            .editor-container {
                padding: 1.5rem;
            }

            .editor-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .editor-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">BlagStrike</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="editor-container">
            <div class="editor-header">
                <h1><?php echo isset($_GET['edit']) ? 'Edit Post' : 'Create New Post'; ?></h1>
                <a href="index.php" class="btn btn-outline">← Back to Posts</a>
            </div>
            
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="blog.php<?php echo isset($_GET['edit']) ? '?edit=' . $_GET['edit'] : ''; ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required placeholder="Enter your post title">
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" required placeholder="Write your post content here..."><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
                <div class="editor-actions">
                    <button type="submit" class="btn"><?php echo isset($_GET['edit']) ? 'Update Post' : 'Publish Post'; ?></button>
                    <a href="index.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>

            <div class="editor-tips">
                <h3>Writing Tips:</h3>
                <ul>
                    <li>Keep your title clear and engaging</li>
                    <li>Use paragraphs to break up your content</li>
                    <li>Proofread before publishing</li>
                    <li>You can edit your post later</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html> 