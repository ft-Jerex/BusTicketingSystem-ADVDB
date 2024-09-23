<?php
require_once 'database.php';

class OccupiedSeats extends Database
{
    public function getSeats($route_id, $date_book)
    {
        $db = $this->connect();
        $sql = "SELECT seat_taken FROM booking WHERE fk_route_id = ? AND date_book = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$route_id, $date_book]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$occupiedSeats = new OccupiedSeats();
$seats = $occupiedSeats->getSeats($_GET['route_id'], $_GET['date_book']);

header('Content-Type: application/json');
echo json_encode($seats);