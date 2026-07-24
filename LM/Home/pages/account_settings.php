
<?php
// Pull session vars
$username   = $_SESSION['username'] ?? '';
$nameofuser      = $_SESSION['Name_of_user']    ?? '';


$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <title>Update Profile</title>
  <style>
   
    .card {
      background: white;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
      padding: 2rem;
      width: 40%;
    }
    h1 {
      margin-top: 0;
      font-size: 1.65rem;
      color: #1a1a1a;
    }
    label {
      display: block;
      margin: 1.3rem 0 0.45rem;
      font-weight: 500;
      color: #333;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 1rem;
      box-sizing: border-box;
      transition: border-color 0.15s;
    }
    input:focus {
      outline: none;
      border-color: #3b82f6;
      box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
    }
    .btn1 {
      margin-top: 1.8rem;
      width: 100%;
      padding: 0.9rem;
      background: #2563eb;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 1.05rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn1:hover {
      background: #1d4ed8;
    }
    .btn1:disabled {
      background: #9ca3af;
      cursor: not-allowed;
    }
    .message {
      padding: 1rem;
      border-radius: 6px;
      margin: 1.2rem 0;
      font-size: 0.95rem;
    }
    .success {
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
    }
    .error {
      background: #fef2f2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }
    .note {
      color: #6b7280;
      font-size: 0.85rem;
      margin-top: -0.3rem;
      display: block;
    }
    .field-group {
      margin-bottom: 0.4rem;
    }
  </style>
</head>
<body>

<div class="card">
  <h1>Update Profile</h1>

  <div id="messageArea" class="message" style="display:none;"></div>

  <form id="profileForm" autocomplete="off">

    <div class="field-group">
      <label for="full_name">Full Name</label>
      <input type="text" id="full_name" name="full_name" readonly value="<?php echo $nameofuser ?>" />
    </div>

    <div class="field-group">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" readonly value="<?php echo $username ?>" />
    </div>

    <div class="field-group">
      <label for="password">New Password <span class="note">(leave blank to keep current password)</span></label>
      <input type="password" id="password" name="password" autocomplete="new-password" />
    </div>

    <div class="field-group">
      <label for="password2">Confirm New Password</label>
      <input type="password" id="password2" name="password2" autocomplete="new-password" />
    </div>

    <button type="submit" class="btn1" id="submitBtn">Save Changes</button>
  </form>
</div>

<script>
const form = document.getElementById('profileForm');
const messageArea = document.getElementById('messageArea');
const submitBtn = document.getElementById('submitBtn');

// Store original values for change tracking
const originalValues = {
    full_name: document.getElementById('full_name').value,
    username: document.getElementById('username').value
};

form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Clear previous messages
    messageArea.style.display = 'none';
    messageArea.className = 'message';

    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Validation
    if (!data.full_name.trim()) {
        showMessage('Full name is required.', 'error');
        return;
    }

    if (!data.username.trim()) {
        showMessage('Username is required.', 'error');
        return;
    }

    if (data.password && !data.password.trim()) {
        showMessage('Password is required.', 'error');
        return;
    }

    // Password validation (if provided)
    if (data.password) {

        
        if (data.password !== data.password2) {
            showMessage('Passwords not match.', 'error');
            return;
        }
        
      
    }

    // Check if any changes were made
    if (data.password === '' && 
        data.full_name === originalValues.full_name && 
        data.username === originalValues.username) {
        showMessage('No changes were made to your profile.', 'info');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    try {
        const response = await fetch('/LM/datafetcher/accountupdate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.error || 'Something went wrong');
        }

        if (result.success) {
            showMessage(result.message || 'Profile updated successfully!', 'success');
            
            // Update original values
            originalValues.full_name = data.full_name;
            originalValues.username = data.username;
            
            // Clear password fields after success
            document.getElementById('password').value = '';
            document.getElementById('password2').value = '';
            
            // Optional: Refresh page after 2 seconds to reflect changes
            // setTimeout(() => location.reload(), 2000);
        } else {
            showMessage(result.error || 'Failed to update profile.', 'error');
        }

    } catch (err) {
        showMessage(err.message || 'Failed to update profile. Please try again.', 'error');
        console.error('Update error:', err);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Changes';
    }
});

function showMessage(text, type) {
    messageArea.textContent = text;
    messageArea.className = `message ${type}`;
    messageArea.style.display = 'block';
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageArea.style.display = 'none';
        }, 5000);
    }
}
</script>

</body>
</html>