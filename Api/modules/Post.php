<?php
// Post.php
class Post {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insertTransaction($param) {
        $dt = $param->payload[0];
        $currentBalance = $this->getCurrentBalance();
    
        $newBalance = $dt->transac_type === 'Withdrawal'
            ? $currentBalance - $dt->amount
            : $currentBalance + $dt->amount;
    
        if ($newBalance < 0) {
            return [
                "success" => false,
                "error" => "Transaction would result in a negative balance"
            ];
        }
    
        $sqlString = "INSERT INTO transactions (transac_type, amount, balance_after, timestamp) VALUES (?,?,?,?)";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([
                $dt->transac_type,
                $dt->amount,
                $newBalance,
                (new \DateTime())->format('Y-m-d H:i:s') 
            ]);
            return [
                "success" => true,
                "message" => "Transaction saved successfully",
                "id" => $this->pdo->lastInsertId()
            ];
        } catch (\PDOException $e) {
            return [
                "success" => false,
                "error" => $e->getMessage(),
                "code" => $e->getCode()
            ];
        }
    }

    private function getCurrentBalance() {
        $sqlString = "SELECT balance_after FROM transactions ORDER BY id DESC LIMIT 1";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result ? (float) $result['balance_after'] : 0.0;
        } catch (\Throwable $th) {
            return 0.0;
        }
    }

    public function updateTransaction($param) {
        $sqlString = "UPDATE transactions SET transac_type=?, amount=?, balance_after=?, timestamp=? WHERE id=?";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([
                $param->transac_type,
                $param->amount,
                $param->balance_after,
                $param->timestamp,
                $param->id
            ]);
            return ["success" => true, "message" => "Transaction updated successfully"];
        } catch (\Throwable $th) {
            return [
                "success" => false,
                "msg" => "Unable to update data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
    }

    public function deleteTransaction($param) {
        $sqlString = "DELETE FROM transactions WHERE id=?";
        try {
            $stmt = $this->pdo->prepare($sqlString);
            $stmt->execute([$param]);
            return ["success" => true, "message" => "Transaction deleted successfully"];
        } catch (\Throwable $th) {
            return [
                "success" => false,
                "msg" => "Unable to delete data", 
                "error" => $th->getMessage(),
                "code" => $th->getCode()
            ];
        }
    }
}
?>