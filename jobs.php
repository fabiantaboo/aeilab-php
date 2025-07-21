<?php
require_once 'includes/bootstrap.php';

// Check if user is logged in
$user->requireLogin();

$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$user->validateCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        switch ($action) {
            case 'retry_job':
                $jobId = intval($_POST['job_id']);
                if ($dialogJob->retryJob($jobId)) {
                    $success = 'Job wurde erfolgreich für erneute Verarbeitung markiert.';
                } else {
                    $error = 'Job konnte nicht erneut gestartet werden.';
                }
                break;
            
            case 'retry_all_failed':
                $retriedCount = $dialogJob->retryFailedJobs();
                if ($retriedCount > 0) {
                    $success = "$retriedCount fehlgeschlagene Jobs wurden für erneute Verarbeitung markiert.";
                } else {
                    $error = 'Keine fehlgeschlagenen Jobs zum Wiederholen gefunden.';
                }
                break;
        }
    }
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Get jobs based on filter
if ($status === 'all') {
    $activeJobs = $dialogJob->getActiveJobs();
    $failedJobs = $dialogJob->getFailedJobs();
    $jobs = array_merge($activeJobs, $failedJobs);
} else if ($status === 'failed') {
    $jobs = $dialogJob->getFailedJobs();
} else if ($status === 'pending') {
    $jobs = array_filter($dialogJob->getActiveJobs(), function($job) {
        return $job['status'] === 'pending';
    });
} else if ($status === 'in_progress') {
    $jobs = array_filter($dialogJob->getActiveJobs(), function($job) {
        return $job['status'] === 'in_progress';
    });
} else if ($status === 'completed') {
    $jobs = array_filter($dialogJob->getActiveJobs(), function($job) {
        return $job['status'] === 'completed';
    });
} else {
    $jobs = $dialogJob->getActiveJobs(); // Default fallback
}

// Get statistics
$stats = $dialogJob->getStats();

// Handle AJAX requests for real-time updates
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'stats' => $stats,
        'jobs' => $jobs,
        'timestamp' => time()
    ]);
    exit;
}

includeHeader('Dialog Jobs - AEI Lab');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-cogs"></i> Dialog Jobs</h1>
                <div class="btn-group">
                    <a href="dialogs.php" class="btn btn-outline-primary">
                        <i class="fas fa-comments"></i> Back to Dialogs
                    </a>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-50 small">Total Jobs</div>
                                    <div class="h4 mb-0"><?php echo $stats['total']; ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-tasks fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-50 small">Pending</div>
                                    <div class="h4 mb-0"><?php echo $stats['pending']; ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-50 small">In Progress</div>
                                    <div class="h4 mb-0"><?php echo $stats['in_progress']; ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-spinner fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-50 small">Completed</div>
                                    <div class="h4 mb-0"><?php echo $stats['completed']; ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-50 small">Failed</div>
                                    <div class="h4 mb-0"><?php echo $stats['failed']; ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exclamation-triangle fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="text-white-50 small">Last Update</div>
                                    <div class="h6 mb-0" id="lastUpdate"><?php echo date('H:i:s'); ?></div>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-sync-alt fa-2x text-white-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Jobs Table -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Active Jobs</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshJobs()">
                                <i class="fas fa-refresh"></i> Refresh
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleAutoRefresh()">
                                <i class="fas fa-play"></i> Auto Refresh
                            </button>
                            <?php if ($stats['failed'] > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="retryAllFailed()">
                                    <i class="fas fa-redo"></i> Retry All Failed (<?php echo $stats['failed']; ?>)
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Status Filter Buttons -->
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <a href="?status=all" class="btn btn-sm <?php echo $status === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                Alle Jobs
                            </a>
                            <a href="?status=pending" class="btn btn-sm <?php echo $status === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                Pending
                            </a>
                            <a href="?status=in_progress" class="btn btn-sm <?php echo $status === 'in_progress' ? 'btn-info' : 'btn-outline-info'; ?>">
                                In Progress
                            </a>
                            <a href="?status=completed" class="btn btn-sm <?php echo $status === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">
                                Completed
                            </a>
                            <a href="?status=failed" class="btn btn-sm <?php echo $status === 'failed' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                Failed (<?php echo $stats['failed']; ?>)
                            </a>
                        </div>
                    </div>
                    
                    <?php if (empty($jobs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No active jobs found</h5>
                            <p class="text-muted">Create a new dialog to start generating background jobs.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Job ID</th>
                                        <th>Dialog</th>
                                        <th>Topic</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Next Turn</th>
                                        <th>Last Processed</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="jobsTable">
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td>
                                                <code>#<?php echo $job['id']; ?></code>
                                            </td>
                                            <td>
                                                <a href="dialog-view.php?id=<?php echo $job['dialog_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($job['dialog_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($job['topic']); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = [
                                                    'pending' => 'warning',
                                                    'in_progress' => 'info',
                                                    'completed' => 'success',
                                                    'failed' => 'danger'
                                                ];
                                                $statusIcons = [
                                                    'pending' => 'clock',
                                                    'in_progress' => 'spinner fa-spin',
                                                    'completed' => 'check-circle',
                                                    'failed' => 'exclamation-triangle'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $statusClass[$job['status']]; ?>">
                                                    <i class="fas fa-<?php echo $statusIcons[$job['status']]; ?>"></i>
                                                    <?php echo ucfirst($job['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo round(($job['current_turn'] / $job['max_turns']) * 100, 1); ?>%">
                                                        <?php echo $job['current_turn']; ?>/<?php echo $job['max_turns']; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $job['next_character_type'] === 'AEI' ? 'primary' : 'secondary'; ?>">
                                                    <?php echo $job['next_character_type']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($job['last_processed_at']): ?>
                                                    <small class="text-muted">
                                                        <?php echo date('H:i:s', strtotime($job['last_processed_at'])); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">Never</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="dialog-view.php?id=<?php echo $job['dialog_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($job['status'] === 'failed'): ?>
                                                        <button class="btn btn-outline-info btn-sm" 
                                                                onclick="showErrorDetails(<?php echo $job['id']; ?>)"
                                                                title="Fehlerdetails anzeigen">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                        <button class="btn btn-outline-success btn-sm" 
                                                                onclick="retryJob(<?php echo $job['id']; ?>)"
                                                                title="Job erneut starten">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let autoRefreshInterval;
let autoRefreshEnabled = false;

function refreshJobs() {
    fetch('?ajax=1')
        .then(response => response.json())
        .then(data => {
            updateStats(data.stats);
            updateJobsTable(data.jobs);
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
        })
        .catch(error => {
            console.error('Error refreshing jobs:', error);
        });
}

function updateStats(stats) {
    // Update statistics cards
    document.querySelector('.card.bg-primary .h4').textContent = stats.total;
    document.querySelector('.card.bg-warning .h4').textContent = stats.pending;
    document.querySelector('.card.bg-info .h4').textContent = stats.in_progress;
    document.querySelector('.card.bg-success .h4').textContent = stats.completed;
    document.querySelector('.card.bg-danger .h4').textContent = stats.failed;
}

function updateJobsTable(jobs) {
    const tbody = document.getElementById('jobsTable');
    // This would need to be implemented based on the jobs data
    // For now, we'll just refresh the page
    if (jobs.length !== tbody.children.length) {
        location.reload();
    }
}

function toggleAutoRefresh() {
    const button = event.target.closest('button');
    if (autoRefreshEnabled) {
        clearInterval(autoRefreshInterval);
        autoRefreshEnabled = false;
        button.innerHTML = '<i class="fas fa-play"></i> Auto Refresh';
        button.classList.remove('btn-outline-success');
        button.classList.add('btn-outline-info');
    } else {
        autoRefreshInterval = setInterval(refreshJobs, 5000); // Refresh every 5 seconds
        autoRefreshEnabled = true;
        button.innerHTML = '<i class="fas fa-pause"></i> Stop Auto Refresh';
        button.classList.remove('btn-outline-info');
        button.classList.add('btn-outline-success');
    }
}

function showErrorDetails(jobId) {
    // This would show a modal with error details
    alert('Error details for job #' + jobId);
}

function retryJob(jobId) {
    if (confirm('Sind Sie sicher, dass Sie diesen Job erneut starten möchten?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'retry_job';
        form.appendChild(actionInput);
        
        const jobIdInput = document.createElement('input');
        jobIdInput.type = 'hidden';
        jobIdInput.name = 'job_id';
        jobIdInput.value = jobId;
        form.appendChild(jobIdInput);
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $user->generateCSRFToken(); ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function retryAllFailed() {
    const failedCount = <?php echo $stats['failed']; ?>;
    if (confirm(`Sind Sie sicher, dass Sie alle ${failedCount} fehlgeschlagenen Jobs erneut starten möchten?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'retry_all_failed';
        form.appendChild(actionInput);
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $user->generateCSRFToken(); ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto refresh every 10 seconds by default
document.addEventListener('DOMContentLoaded', function() {
    setInterval(refreshJobs, 10000);
});
</script>

<?php includeFooter(); ?> 