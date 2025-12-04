<?php

namespace App\Controllers;

class MaintenanceController extends BaseController {
    private $backupDir;
    
    public function __construct() {
        parent::__construct();
        $this->backupDir = __DIR__ . '/../../backups';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    public function index() {
        // Get list of existing backups
        $backups = $this->getBackupList();
        
        // Get snapshot info
        $stmt = $this->db->query("SELECT COUNT(*) as count, MIN(snapshot_date) as first_date, MAX(snapshot_date) as last_date FROM balance_snapshots");
        $snapshotInfo = $stmt->fetch();
        
        $this->render('maintenance.twig', [
            'backups' => $backups,
            'snapshotInfo' => $snapshotInfo
        ]);
    }
    
    public function rebuildSnapshots() {
        header('Content-Type: application/json');
        
        try {
            $snapshotController = new BalanceSnapshotController();
            $count = $snapshotController->rebuildAllSnapshots();
            
            echo json_encode([
                'success' => true,
                'count' => $count,
                'message' => "$count snapshots recalculés avec succès"
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function createBackup() {
        header('Content-Type: application/json');
        
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "dinero_backup_{$timestamp}.sql";
            $filepath = $this->backupDir . '/' . $filename;
            
            // Create SQL dump
            $sql = $this->generateSQLDump();
            
            // Write to file
            file_put_contents($filepath, $sql);
            
            // Get file size
            $filesize = filesize($filepath);
            
            // Cleanup old backups (keep only 5 most recent)
            $this->cleanupOldBackups();
            
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'size' => $this->formatBytes($filesize),
                'timestamp' => $timestamp
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function restoreBackup() {
        header('Content-Type: application/json');
        
        $filename = $_POST['filename'] ?? '';
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            echo json_encode(['success' => false, 'error' => 'Fichier non trouvé']);
            return;
        }
        
        try {
            // Read SQL file
            $sql = file_get_contents($filepath);
            
            // Disable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            // Execute each statement
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $this->db->exec($statement);
                }
            }
            
            // Re-enable foreign key checks
            $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
            
            echo json_encode([
                'success' => true,
                'message' => 'Base de données restaurée avec succès'
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la restauration: ' . $e->getMessage()
            ]);
        }
    }
    
    public function downloadBackup($filename) {
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            header('HTTP/1.0 404 Not Found');
            echo "Fichier non trouvé";
            return;
        }
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
    
    public function deleteBackup() {
        header('Content-Type: application/json');
        
        $filename = $_POST['filename'] ?? '';
        $filepath = $this->backupDir . '/' . basename($filename);
        
        if (!file_exists($filepath)) {
            echo json_encode(['success' => false, 'error' => 'Fichier non trouvé']);
            return;
        }
        
        if (unlink($filepath)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Impossible de supprimer le fichier']);
        }
    }
    
    private function generateSQLDump() {
        $sql = "-- Dinero Database Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Get all tables
        $tables = ['users', 'banks', 'accounts', 'transactions'];
        
        foreach ($tables as $table) {
            // Get table structure
            $sql .= "-- Table structure for `{$table}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            
            $stmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $sql .= $row['Create Table'] . ";\n\n";
            
            // Get table data
            $sql .= "-- Data for table `{$table}`\n";
            $stmt = $this->db->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array_map(function($value) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return "'" . addslashes($value) . "'";
                    }, array_values($row));
                    
                    $columns = '`' . implode('`, `', array_keys($row)) . '`';
                    $sql .= "INSERT INTO `{$table}` ({$columns}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $sql;
    }
    
    private function getBackupList() {
        $backups = [];
        
        if (!is_dir($this->backupDir)) {
            return $backups;
        }
        
        $files = scandir($this->backupDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filepath = $this->backupDir . '/' . $file;
            
            if (is_file($filepath) && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $backups[] = [
                    'filename' => $file,
                    'size' => $this->formatBytes(filesize($filepath)),
                    'date' => date('Y-m-d H:i:s', filemtime($filepath))
                ];
            }
        }
        
        // Sort by date descending
        usort($backups, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });
        
        return $backups;
    }
    
    private function cleanupOldBackups() {
        $files = [];
        
        if (!is_dir($this->backupDir)) {
            return;
        }
        
        $dirFiles = scandir($this->backupDir);
        
        foreach ($dirFiles as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filepath = $this->backupDir . '/' . $file;
            
            if (is_file($filepath) && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $files[] = [
                    'filename' => $file,
                    'filepath' => $filepath,
                    'mtime' => filemtime($filepath)
                ];
            }
        }
        
        // Sort by modification time descending
        usort($files, function($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });
        
        // Keep only 5 most recent, delete the rest
        if (count($files) > 5) {
            $filesToDelete = array_slice($files, 5);
            
            foreach ($filesToDelete as $file) {
                unlink($file['filepath']);
            }
        }
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
