<?php
// Display success message if set
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['success']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['success']);
}

// Display error message if set
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['error']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error']);
}

// Display warning message if set
if (isset($_SESSION['warning'])) {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['warning']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['warning']);
}

// Display info message if set
if (isset($_SESSION['info'])) {
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($_SESSION['info']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['info']);
}
?>