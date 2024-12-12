<?php
// Get.php
class Get {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getTransactions() {
        $sqlString = "SELECT * FROM transactions";
        $res = [];
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute();
            $res = $stmt->fetchAll();
        } catch (\Throwable $th) {
            $res = [
                "msg" => "Unable to fetch data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
        return $res;
    }
}
?>