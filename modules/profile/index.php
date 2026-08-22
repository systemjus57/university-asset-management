<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
requireLogin();

$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

$errors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'profile') {
        $name     = clean($_POST['name'] ?? '');
        $email    = clean($_POST['email'] ?? '');
        $username = clean($_POST['username'] ?? '');

        if ($name === '' || $email === '' || $username === '') {
            $errors[] = 'Name, email, and username are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
            $errors[] = 'Username must be 3-50 characters, using letters, numbers, dots, underscores, or hyphens only.';
        } else {
            $check = $pdo->prepare('SELECT 1 FROM users WHERE email = :email AND user_id != :id');
            $check->execute(['email' => $email, 'id' => $userId]);
            if ($check->fetch()) {
                $errors[] = 'Another account already uses this email.';
            }
            $check = $pdo->prepare('SELECT 1 FROM users WHERE username = :username AND user_id != :id');
            $check->execute(['username' => $username, 'id' => $userId]);
            if ($check->fetch()) {
                $errors[] = 'Another account already uses this username.';
            }
        }

        // Profile picture upload (optional) / removal
        $removePicture = isset($_POST['remove_picture']);
        $picturePath = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            if ($_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Profile picture upload failed. Please try again.';
            } elseif (!in_array($ext, $allowedExt, true)) {
                $errors[] = 'Profile picture must be a JPG, PNG, or GIF image.';
            } elseif ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Profile picture must be smaller than 2MB.';
            } elseif (!isValidImageUpload($_FILES['profile_picture']['tmp_name'])) {
                $errors[] = 'That file is not a valid image.';
            } else {
                $newName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $destination = APP_ROOT . '/uploads/avatars/' . $newName;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                    $picturePath = APP_URL . '/uploads/avatars/' . $newName;
                } else {
                    $errors[] = 'Could not save the uploaded profile picture.';
                }
            }
        }

        if (!$errors) {
            if ($picturePath || $removePicture) {
                // Remove the previous picture file so uploads/avatars doesn't accumulate orphans.
                if (!empty($user['profile_picture'])) {
                    $oldFile = APP_ROOT . '/uploads/avatars/' . basename($user['profile_picture']);
                    if (is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                $pdo->prepare('UPDATE users SET name = :name, email = :email, username = :username, profile_picture = :picture WHERE user_id = :id')
                    ->execute(['name' => $name, 'email' => $email, 'username' => $username, 'picture' => $picturePath, 'id' => $userId]);
                $_SESSION['profile_picture'] = $picturePath;
            } else {
                $pdo->prepare('UPDATE users SET name = :name, email = :email, username = :username WHERE user_id = :id')
                    ->execute(['name' => $name, 'email' => $email, 'username' => $username, 'id' => $userId]);
            }
            $_SESSION['name']     = $name;
            $_SESSION['email']    = $email;
            $_SESSION['username'] = $username;
            logActivity($pdo, $userId, 'Update Profile', 'profile', 'Updated own profile details.');
            flash('success', 'Profile updated successfully.');
            redirect(APP_URL . '/modules/profile/index.php');
        }
    } elseif ($formType === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if (!password_verify($current, $user['password'])) {
            $passwordErrors[] = 'Current password is incorrect.';
        }
        if (strlen($new) < 6) {
            $passwordErrors[] = 'New password must be at least 6 characters.';
        }
        if ($new !== $confirm) {
            $passwordErrors[] = 'New password and confirmation do not match.';
        }

        if (!$passwordErrors) {
            $pdo->prepare('UPDATE users SET password = :password WHERE user_id = :id')
                ->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => $userId]);
            logActivity($pdo, $userId, 'Change Password', 'profile', 'Changed own account password.');
            flash('success', 'Password changed successfully.');
            redirect(APP_URL . '/modules/profile/index.php');
        }
    }
}

$pageTitle  = 'My Profile';
$activeMenu = '';
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="tabs">
    <div class="tab-link active" data-tab="tab-profile">Profile</div>
    <div class="tab-link" data-tab="tab-password">Change Password</div>
</div>

<div id="tab-profile" class="tab-panel active">
    <div class="card" style="max-width:560px;">
        <?php if ($errors): ?>
            <div class="alert alert-error"><ul style="margin:0; padding-left:1.1rem;"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="" enctype="multipart/form-data" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="form_type" value="profile">

            <div class="form-group">
                <label>Profile Picture</label>
                <?php if (!empty($user['profile_picture'])): ?>
                    <img class="profile-avatar-preview" src="<?= e($user['profile_picture']) ?>" alt="">
                <?php else: ?>
                    <div class="profile-avatar-placeholder"><?= e(mb_strtoupper(mb_substr($user['name'] ?? 'A', 0, 1))) ?></div>
                <?php endif; ?>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/png,image/jpeg,image/gif">
                <?php if (!empty($user['profile_picture'])): ?>
                    <label class="d-flex align-center gap-1 mt-1" style="font-weight:400;">
                        <input type="checkbox" name="remove_picture" value="1" style="width:auto;">
                        Remove current photo
                    </label>
                <?php endif; ?>
            </div>

            <div class="form-group"><label for="name">Full Name *</label><input type="text" id="name" name="name" required value="<?= e($user['name']) ?>"></div>
            <div class="form-group"><label for="email">Email *</label><input type="email" id="email" name="email" required value="<?= e($user['email']) ?>"></div>
            <div class="form-group"><label for="username">Username *</label><input type="text" id="username" name="username" required minlength="3" maxlength="50" value="<?= e($user['username']) ?>"></div>
            <div class="form-group"><label>Role</label><input type="text" value="<?= e($_SESSION['role_name']) ?>" disabled></div>
            <div class="form-group"><label>Department</label><input type="text" value="<?= e($_SESSION['department_name'] ?? 'N/A') ?>" disabled></div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</div>

<div id="tab-password" class="tab-panel">
    <div class="card" style="max-width:560px;">
        <?php if ($passwordErrors): ?>
            <div class="alert alert-error"><ul style="margin:0; padding-left:1.1rem;"><?php foreach ($passwordErrors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="post" action="" novalidate>
            <?= csrfField() ?>
            <input type="hidden" name="form_type" value="password">
            <div class="form-group"><label for="current_password">Current Password *</label><input type="password" id="current_password" name="current_password" required></div>
            <div class="form-group"><label for="new_password">New Password *</label><input type="password" id="new_password" name="new_password" required minlength="6"></div>
            <div class="form-group"><label for="confirm_password">Confirm New Password *</label><input type="password" id="confirm_password" name="confirm_password" required minlength="6"></div>
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
