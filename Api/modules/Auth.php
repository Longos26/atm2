<?php
class Auth {
    protected $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
    }

    protected function checkPassword(string $password, string $hashedPassword): bool {
        return password_verify($password, $hashedPassword);
    }

    public function encryptPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function generateSalt(int $length): string {
        throw new Exception("Custom salt generation is no longer recommended. Use password_hash instead.");
    }

    public function encryptData(array $data): string {
        try {
            $json = json_encode($data);
            if ($json === false) {
                throw new Exception("Failed to encode data to JSON");
            }

            $iv = random_bytes(16);
            $key = getenv('ENCRYPTION_KEY') ?: throw new Exception("Encryption key is not set");

            $encryptedData = openssl_encrypt($json, "AES-256-CBC", $key, 0, $iv);
            if ($encryptedData === false) {
                throw new Exception("Encryption failed");
            }

            $payload = [
                "data" => $encryptedData,
                "iv" => base64_encode($iv)
            ];

            return base64_encode(json_encode($payload));
        } catch (Exception $e) {
            throw new Exception("Encryption error: " . $e->getMessage());
        }
    }

    public function decryptData(string $encryptedPayload): mixed {
        try {
            $payload = json_decode(base64_decode($encryptedPayload), true);
            if (!$payload || !isset($payload["data"], $payload["iv"])) {
                throw new Exception("Invalid encrypted payload");
            }

            $iv = base64_decode($payload["iv"]);
            if ($iv === false) {
                throw new Exception("Failed to decode IV");
            }

            $key = getenv('ENCRYPTION_KEY') ?: throw new Exception("Encryption key is not set");

            $decryptedData = openssl_decrypt($payload['data'], "AES-256-CBC", $key, 0, $iv);
            if ($decryptedData === false) {
                throw new Exception("Decryption failed");
            }

            return json_decode($decryptedData, true);
        } catch (Exception $e) {
            throw new Exception("Decryption error: " . $e->getMessage());
        }
    }
}