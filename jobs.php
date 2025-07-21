<?php
require_once 'includes/bootstrap.php';

// Check if user is logged in
$user->requireLogin();

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Get jobs based on filter
if ($status === 'all') {
    $jobs = $dialogJob->getActiveJobs();
} else {
    $jobs = $dialogJob->getActiveJobs(); // Filter by status would be implemented here
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
                        </div>
                    </div>
                </div>
                <div class="card-body">
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
                                        <th>Restarts</th>
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
                                                <?php if (isset($job['restart_count']) && $job['restart_count'] > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-redo"></i> <?php echo $job['restart_count']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <small class="text-muted">0</small>
                                                <?php endif; ?>
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
                                                                onclick="showErrorDetails(<?php echo $job['id']; ?>)">
                                                            <i class="fas fa-info-circle"></i>
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

// Auto refresh every 10 seconds by default
document.addEventListener('DOMContentLoaded', function() {
    setInterval(refreshJobs, 10000);
});
</script>

<?php includeFooter(); ?> 