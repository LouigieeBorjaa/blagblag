<?php
require_once 'vendor/autoload.php';

use Aries\Dbmodel\Includes\Database;
use Aries\Dbmodel\Includes\Session;

$session = new Session();
$database = new Database();
$conn = $database->getConnection();

// Function to get recent posts
function getRecentPosts($conn, $userId = null) {
    if ($userId) {
        $sql = "SELECT posts.*, users.first_name, users.last_name 
                FROM posts 
                JOIN users ON posts.author_id = users.id 
                WHERE posts.author_id = :user_id 
                ORDER BY posts.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
    } else {
        $sql = "SELECT posts.*, users.first_name, users.last_name 
                FROM posts 
                JOIN users ON posts.author_id = users.id 
                ORDER BY posts.created_at DESC";
        $stmt = $conn->prepare($sql);
    }
    $stmt->execute();
    return $stmt;
}

// Regenerate session ID periodically
$session->regenerate();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BlagStrike</title>
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

        .btn-group {
            display: flex;
            gap: 1rem;
        }

        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 3rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-banner h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .welcome-banner p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .post {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .post:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .post h2 {
            color: #1a1a1a;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .post-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .post-meta .author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-meta .author::before {
            content: "👤";
        }

        .post-meta .date::before {
            content: "📅";
            margin-right: 0.5rem;
        }

        .post-content {
            color: #4a4a4a;
            margin-bottom: 1.5rem;
            white-space: pre-wrap;
        }

        .post-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
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

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
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

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .empty-state h2 {
            color: #1a1a1a;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .navbar {
                padding: 1rem;
            }

            .main-content {
                padding: 0 1rem;
            }

            .welcome-banner {
                padding: 2rem 1rem;
            }

            .welcome-banner h1 {
                font-size: 2rem;
            }

            .post {
                padding: 1.5rem;
            }

            .btn-group {
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
            <div class="btn-group">
                <?php if($session->isLoggedIn()): ?>
                    <a href="blog.php" class="btn">Create New Post</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login</a>
                    <a href="register.php" class="btn btn-outline">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <?php if($session->isLoggedIn()): ?>
            <div class="welcome-banner">
                <h1>Welcome, <?php echo htmlspecialchars($session->getUserName()); ?>!</h1>
                <p>Share your thoughts with the world.</p>
            </div>
        <?php endif; ?>

        <?php
        $posts = getRecentPosts($conn, $session->getUserId());
        if($posts->rowCount() > 0):
            while($post = $posts->fetch(PDO::FETCH_ASSOC)):
        ?>
            <article class="post">
                <h2><?php echo htmlspecialchars($post['title']); ?></h2>
                <div class="post-meta">
                    <span class="author"><?php echo htmlspecialchars($post['first_name'] . ' ' . $post['last_name']); ?></span>
                    <span class="date"><?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                </div>
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                <?php if($session->isLoggedIn() && $session->getUserId() == $post['author_id']): ?>
                    <div class="post-actions">
                        <a href="blog.php?edit=<?php echo $post['id']; ?>" class="btn btn-outline">Edit Post</a>
                    </div>
                <?php endif; ?>
            </article>
        <?php 
            endwhile;
        else:
        ?>
            <div class="empty-state">
                <h2>No Posts Yet</h2>
                <p>Be the first to share your thoughts!</p>
                <?php if($session->isLoggedIn()): ?>
                    <a href="blog.php" class="btn">Create Your First Post</a>
                <?php else: ?>
                    <a href="login.php" class="btn">Login to Create Posts</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>