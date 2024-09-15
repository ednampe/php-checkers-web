<?php
session_start();

class CheckersGame {
    public $board;
    public $currentPlayer;
    public $gameOver;
    public $winner;
    public $mustCapture;
    public $justKinged;

    public function __construct() {
        $this->initializeBoard();
        $this->currentPlayer = 1;
        $this->gameOver = false;
        $this->winner = null;
        $this->mustCapture = false;
        $this->justKinged = false;
    }

    private function initializeBoard() {
        $this->board = array_fill(0, 8, array_fill(0, 8, 0));
        for ($row = 0; $row < 3; $row++) {
            for ($col = ($row + 1) % 2; $col < 8; $col += 2) {
                $this->board[$row][$col] = 1;
            }
        }
        for ($row = 5; $row < 8; $row++) {
            for ($col = ($row + 1) % 2; $col < 8; $col += 2) {
                $this->board[$row][$col] = 2;
            }
        }
    }

    public function makeMove($fromRow, $fromCol, $toRow, $toCol) {
        $piece = $this->board[$fromRow][$fromCol];
        if ($piece != $this->currentPlayer && $piece != $this->currentPlayer + 2) {
            return ['success' => false, 'message' => 'Not your piece'];
        }

        if ($this->mustCapture && !$this->isCapturingMove($fromRow, $fromCol, $toRow, $toCol)) {
            return ['success' => false, 'message' => 'Must make a capturing move'];
        }

        if ($this->hasForcedCapture($this->currentPlayer) && !$this->isCapturingMove($fromRow, $fromCol, $toRow, $toCol)) {
            return ['success' => false, 'message' => 'Must make a capturing move when available'];
        }

        if ($this->isValidMove($fromRow, $fromCol, $toRow, $toCol)) {
            $this->board[$toRow][$toCol] = $piece;
            $this->board[$fromRow][$fromCol] = 0;

            // Handle capturing
            $isCapture = false;
            if ($this->isCapturingMove($fromRow, $fromCol, $toRow, $toCol)) {
                $capturedRow = ($fromRow + $toRow) / 2;
                $capturedCol = ($fromCol + $toCol) / 2;
                $this->board[$capturedRow][$capturedCol] = 0;
                $isCapture = true;
            }

            // Handle kinging
            $wasKinged = false;
            if (($piece == 1 && $toRow == 7) || ($piece == 2 && $toRow == 0)) {
                $this->board[$toRow][$toCol] += 2;
                $wasKinged = true;
            }

            // Change turn if the piece was kinged
            if ($wasKinged) {
                $this->currentPlayer = 3 - $this->currentPlayer;
                $this->mustCapture = false;
                $this->checkGameOver();
                return ['success' => true, 'continueTurn' => false];
            }

            // Check for multiple captures
            if ($isCapture && $this->hasMoreCaptures($toRow, $toCol)) {
                $this->mustCapture = true;
                return ['success' => true, 'continueTurn' => true, 'captureRow' => $toRow, 'captureCol' => $toCol];
            } else {
                $this->mustCapture = false;
                $this->currentPlayer = 3 - $this->currentPlayer;
                $this->checkGameOver();
                return ['success' => true, 'continueTurn' => false];
            }
        }

        return ['success' => false, 'message' => 'Invalid move'];
    }

    private function isValidMove($fromRow, $fromCol, $toRow, $toCol) {
        $piece = $this->board[$fromRow][$fromCol];
        $isKing = $piece > 2;

        if ($this->board[$toRow][$toCol] != 0) {
            return false;
        }

        $rowDiff = $toRow - $fromRow;
        $colDiff = abs($toCol - $fromCol);

        // Check for basic move (1 square diagonally)
        if ($colDiff == 1 && (($piece == 1 && $rowDiff == 1) || ($piece == 2 && $rowDiff == -1) || $isKing && abs($rowDiff) == 1)) {
            return true;
        }

        // Check for capture move (2 squares diagonally)
        if ($colDiff == 2 && abs($rowDiff) == 2) {
            $jumpedRow = ($fromRow + $toRow) / 2;
            $jumpedCol = ($fromCol + $toCol) / 2;
            $jumpedPiece = $this->board[$jumpedRow][$jumpedCol];
            return $jumpedPiece != 0 && $jumpedPiece % 2 != $piece % 2 &&
                   ($isKing || ($piece == 1 && $rowDiff == 2) || ($piece == 2 && $rowDiff == -2));
        }

        return false;
    }

    private function isCapturingMove($fromRow, $fromCol, $toRow, $toCol) {
        return abs($toRow - $fromRow) == 2 && abs($toCol - $fromCol) == 2;
    }

    private function hasMoreCaptures($row, $col) {
        $piece = $this->board[$row][$col];
        $isKing = $piece > 2;
        $directions = [[-1, -1], [-1, 1], [1, -1], [1, 1]];
        
        foreach ($directions as $dir) {
            $newRow = $row + $dir[0] * 2;
            $newCol = $col + $dir[1] * 2;
            if ($newRow >= 0 && $newRow < 8 && $newCol >= 0 && $newCol < 8) {
                $jumpedRow = $row + $dir[0];
                $jumpedCol = $col + $dir[1];
                $jumpedPiece = $this->board[$jumpedRow][$jumpedCol];
                if ($jumpedPiece != 0 && $jumpedPiece % 2 != $piece % 2 &&
                    $this->board[$newRow][$newCol] == 0) {
                    if ($isKing || ($piece == 1 && $dir[0] == 1) || ($piece == 2 && $dir[0] == -1)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function hasForcedCapture($player) {
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $this->board[$row][$col];
                if (($piece == $player || $piece == $player + 2) && $this->hasMoreCaptures($row, $col)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function checkGameOver() {
        $player1Pieces = 0;
        $player2Pieces = 0;

        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $this->board[$row][$col];
                if ($piece == 1 || $piece == 3) {
                    $player1Pieces++;
                } elseif ($piece == 2 || $piece == 4) {
                    $player2Pieces++;
                }
            }
        }

        if ($player1Pieces == 0) {
            $this->gameOver = true;
            $this->winner = 2;
        } elseif ($player2Pieces == 0) {
            $this->gameOver = true;
            $this->winner = 1;
        } elseif (!$this->hasAvailableMoves($this->currentPlayer)) {
            $this->gameOver = true;
            $this->winner = 0; // Draw
        }
    }

    private function hasAvailableMoves($player) {
        for ($row = 0; $row < 8; $row++) {
            for ($col = 0; $col < 8; $col++) {
                $piece = $this->board[$row][$col];
                if (($piece == $player || $piece == $player + 2) && !empty($this->getValidMoves($row, $col))) {
                    return true;
                }
            }
        }
        return false;
    }

    private function hasMoves($row, $col) {
        $piece = $this->board[$row][$col];
        $isKing = $piece > 2;
        $directions = $isKing ? [[-1, -1], [-1, 1], [1, -1], [1, 1]] : 
                                ($piece == 1 ? [[1, -1], [1, 1]] : [[-1, -1], [-1, 1]]);
        
        foreach ($directions as $dir) {
            $newRow = $row + $dir[0];
            $newCol = $col + $dir[1];
            if ($newRow >= 0 && $newRow < 8 && $newCol >= 0 && $newCol < 8) {
                if ($this->board[$newRow][$newCol] == 0) {
                    return true;
                }
                $jumpRow = $newRow + $dir[0];
                $jumpCol = $newCol + $dir[1];
                if ($jumpRow >= 0 && $jumpRow < 8 && $jumpCol >= 0 && $jumpCol < 8) {
                    if ($this->board[$jumpRow][$jumpCol] == 0 && 
                        $this->board[$newRow][$newCol] % 2 != $piece % 2) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function getValidMoves($row, $col) {
        $piece = $this->board[$row][$col];
        if ($piece == 0 || $piece % 2 != $this->currentPlayer % 2) {
            return [];
        }

        $validMoves = [];
        $isKing = $piece > 2;
        $directions = $isKing ? [[-1, -1], [-1, 1], [1, -1], [1, 1]] : 
                                ($piece == 1 ? [[1, -1], [1, 1]] : [[-1, -1], [-1, 1]]);

        // Check for forced captures first
        $hasCapture = false;
        foreach ($directions as $dir) {
            $newRow = $row + $dir[0] * 2;
            $newCol = $col + $dir[1] * 2;
            if ($newRow >= 0 && $newRow < 8 && $newCol >= 0 && $newCol < 8) {
                $jumpedRow = $row + $dir[0];
                $jumpedCol = $col + $dir[1];
                $jumpedPiece = $this->board[$jumpedRow][$jumpedCol];
                if ($jumpedPiece != 0 && $jumpedPiece % 2 != $piece % 2 &&
                    $this->board[$newRow][$newCol] == 0) {
                    $validMoves[] = [$newRow, $newCol];
                    $hasCapture = true;
                }
            }
        }

        // If there are no captures, check for regular moves
        if (!$hasCapture) {
            foreach ($directions as $dir) {
                $newRow = $row + $dir[0];
                $newCol = $col + $dir[1];
                if ($newRow >= 0 && $newRow < 8 && $newCol >= 0 && $newCol < 8) {
                    if ($this->board[$newRow][$newCol] == 0) {
                        $validMoves[] = [$newRow, $newCol];
                    }
                }
            }
        }

        return $validMoves;
    }
}

if (!isset($_SESSION['game'])) {
    $_SESSION['game'] = new CheckersGame();
}

$game = $_SESSION['game'];

$message = '';
$selectedPiece = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['select'])) {
        $selectedRow = $_POST['select_row'];
        $selectedCol = $_POST['select_col'];
        $piece = $game->board[$selectedRow][$selectedCol];
        if ($piece != 0 && ($piece % 2 == $game->currentPlayer % 2)) {
            $_SESSION['selectedPiece'] = [$selectedRow, $selectedCol];
        } else {
            $message = "Invalid piece selection.";
        }
    } elseif (isset($_POST['move'])) {
        if (isset($_SESSION['selectedPiece'])) {
            $fromRow = $_SESSION['selectedPiece'][0];
            $fromCol = $_SESSION['selectedPiece'][1];
            $toRow = $_POST['move_row'];
            $toCol = $_POST['move_col'];
            $result = $game->makeMove($fromRow, $fromCol, $toRow, $toCol);
            if ($result['success']) {
                unset($_SESSION['selectedPiece']);
                if (isset($result['continueTurn']) && $result['continueTurn']) {
                    $_SESSION['selectedPiece'] = [$toRow, $toCol];
                    $message = "Multiple capture available. Continue moving the same piece.";
                }
            } else {
                $message = $result['message'];
            }
        } else {
            $message = "Please select a piece first.";
        }
    }
    $_SESSION['game'] = $game;
}

if (isset($_SESSION['selectedPiece'])) {
    $selectedPiece = $_SESSION['selectedPiece'];
}

function renderBoard($game, $selectedPiece) {
    $board = $game->board;
    $output = '<div class="board">';
    $validMoves = $selectedPiece ? $game->getValidMoves($selectedPiece[0], $selectedPiece[1]) : [];

    for ($row = 0; $row < 8; $row++) {
        for ($col = 0; $col < 8; $col++) {
            $squareClass = ($row + $col) % 2 == 0 ? 'light' : 'dark';
            $piece = $board[$row][$col];
            $pieceClass = '';
            if ($piece != 0) {
                $pieceClass = $piece % 2 == 1 ? 'player1' : 'player2';
                if ($piece > 2) {
                    $pieceClass .= ' king';
                }
            }
            $isSelected = $selectedPiece && $selectedPiece[0] == $row && $selectedPiece[1] == $col;
            $selectedClass = $isSelected ? 'selected' : '';
            $isValidMove = in_array([$row, $col], $validMoves);
            
            $output .= "<div class='square $squareClass'>";
            if ($piece == 0 && $selectedPiece && $isValidMove) {
                $output .= "<form method='POST'>";
                $output .= "<input type='hidden' name='move_row' value='$row'>";
                $output .= "<input type='hidden' name='move_col' value='$col'>";
                $output .= "<button type='submit' name='move' class='move-button'>Move</button>";
                $output .= "</form>";
            } elseif ($piece != 0) {
                $output .= "<form method='POST'>";
                $output .= "<input type='hidden' name='select_row' value='$row'>";
                $output .= "<input type='hidden' name='select_col' value='$col'>";
                $output .= "<button type='submit' name='select' class='piece $pieceClass $selectedClass'></button>";
                $output .= "</form>";
            }
            $output .= "</div>";
        }
    }
    $output .= '</div>';
    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Checkers Game</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px;
            min-height: 100vh;
            margin: 0;
        }
        h1 {
            font-size: 36px;
            font-weight: 300;
            color: #333;
            margin-bottom: 20px;
        }
        .game-info {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            width: 400px;
            text-align: center;
        }
        #currentPlayer, #gameStatus {
            margin: 10px 0;
            font-size: 18px;
        }
        .board {
            display: grid;
            grid-template-columns: repeat(8, 60px);
            grid-template-rows: repeat(8, 60px);
            gap: 1px;
            background-color: #8b4513;
            border: 10px solid #8b4513;
            border-radius: 8px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .square {
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }
        .light {
            background: linear-gradient(135deg, #f0d9b5 0%, #e6ccaa 100%);
        }
        .dark {
            background: linear-gradient(135deg, #b58863 0%, #a37b5c 100%);
        }
        .piece {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .player1 {
            background: radial-gradient(circle at 30% 30%, #a40000 0%, #85144b 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .player2 {
            background: radial-gradient(circle at 30% 30%, #222222 0%, #000000 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .king::after {
            content: "♔";
            color: #ffd700;
            font-size: 30px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        .selected {
    box-shadow: 0 0 0 3px #C0B283,
                0 0 10px 3px rgba(192, 178, 131, 0.5);

        .move-button {
            background-color: rgba(255, 215, 0, 0.6);
            border: none;
            width: 40px;
            height: 40px;
            cursor: pointer;
            border-radius: 50%;
            font-weight: bold;
            color: #333;
            font-size: 11px;
            text-align: center;
            text-transform: uppercase;
            transition: all 0.3s ease;

            /* Flexbox for centering */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .move-button:hover {
            background-color: rgba(255, 215, 0, 0.8);
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <h1>PHP Checkers</h1>
    <div class="game-info">
        <div id="currentPlayer">Current Player: <?php echo $game->currentPlayer == 1 ? 'Red' : 'Black'; ?></div>
        <div id="gameStatus"><?php echo $message; ?></div>
        <?php if ($game->gameOver): ?>
            <div id="gameOver">Game Over! <?php echo $game->winner == 0 ? "It's a draw!" : "Player " . $game->winner . " wins!"; ?></div>
        <?php endif; ?>
    </div>
    <?php echo renderBoard($game, $selectedPiece); ?>
</body>
</html>
