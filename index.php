<?php
require 'vendor/autoload.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dotenv\Dotenv;
use Ramsey\Uuid\Uuid;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load tasks from JSON file
$filename = 'tasks.json';
$tasks = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];

// Get current time in Jakarta timezone
date_default_timezone_set('Asia/Jakarta');
$currentTime = date('Y-m-d H:i:s');

// Handle task submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['task'], $_POST['deadline'], $_POST['reminder'])) {
        $newTask = trim($_POST['task']);
        $deadline = $_POST['deadline'];
        $reminder = $_POST['reminder'];
        if ($newTask && $deadline) {
            $uuid = Uuid::uuid4()->toString();
            $tasks[$uuid] = [
                'task' => $newTask,
                'deadline' => $deadline,
                'reminder' => $reminder
            ];
            file_put_contents($filename, json_encode($tasks, JSON_PRETTY_PRINT));
            header('Location: /');
            exit;
        }
    } elseif (isset($_POST['delete'])) {
        $uuidToDelete = $_POST['delete'];
        if (isset($tasks[$uuidToDelete])) {
            unset($tasks[$uuidToDelete]);
            file_put_contents($filename, json_encode($tasks, JSON_PRETTY_PRINT));
            header('Location: /');
            exit;
        }
    }
}

// Function to calculate days remaining
function getDaysRemaining($deadline) {
    $currentDate = new DateTime();
    $deadlineDate = new DateTime($deadline);
    return $deadlineDate->diff($currentDate)->days;
}

// Calculate days remaining for each task
foreach ($tasks as $uuid => $taskInfo) {
    $tasks[$uuid]['daysRemaining'] = getDaysRemaining($taskInfo['deadline']);
}

// Create a Twig instance
$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

// Render the template with current time and task information
echo $twig->render('index.html.twig', [
    'tasks' => $tasks,
    'current_time' => $currentTime
]);
