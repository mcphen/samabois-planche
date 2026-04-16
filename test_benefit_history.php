<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';

use App\Models\Contrat;
use Illuminate\Support\Facades\DB;

try {
    $contrat = Contrat::find(16);
    echo "Contrat 16: " . ($contrat ? "EXISTS (ID: {$contrat->id}, Numero: {$contrat->numero})" : "NOT FOUND") . "\n";
    
    // Check if table exists
    $tableExists = DB::select("
        SELECT name FROM sqlite_master 
        WHERE type='table' 
        AND name='planche_benefit_histories'
    ");
    echo "Table planche_benefit_histories: " . (count($tableExists) > 0 ? "EXISTS" : "NOT FOUND") . "\n";
    
    // Check record count
    if (count($tableExists) > 0) {
        $count = DB::table('planche_benefit_histories')->count();
        echo "Records in history table: " . $count . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
