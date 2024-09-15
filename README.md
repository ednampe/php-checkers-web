# PHP Checkers Web

A modern, interactive web-based Checkers game implemented in PHP. This project demonstrates object-oriented PHP programming, session management, and dynamic HTML generation.

## Technical Overview

### Core Game Logic

The game logic is encapsulated in the `CheckersGame` class, which includes:

- Board representation using a 2D array
- Methods for initializing the game board
- Logic for making moves, including regular moves and captures
- Handling of king pieces
- Forced capture rules
- Game state tracking (current player, game over conditions)

### Server-Side Processing

The game operates on a request-response model using PHP sessions:

1. The game state is stored in the session using `$_SESSION['game']`.
2. Each move is processed via a POST request.
3. The server updates the game state and sends back a new HTML representation of the board.

### Move Processing

Moves are handled through form submissions:

1. Selecting a piece:
   - A form with hidden inputs for row and column is submitted.
   - The selected piece is stored in the session.

2. Moving a piece:
   - Another form submission sends the destination coordinates.
   - The `makeMove` method processes the move, updating the board state.

### Board Rendering

The `renderBoard` function dynamically generates HTML for the current board state:

- It creates a grid of divs representing the board squares.
- Pieces are represented by buttons within forms.
- Valid moves are displayed as clickable buttons when a piece is selected.

### Styling

The game uses CSS for styling, including:

- A responsive grid layout for the board
- Gradients and box-shadows for a modern look
- CSS transitions for smooth interactions

### Key Features

1. **OOP Approach**: The game logic is encapsulated in a class, promoting code organization and reusability.

2. **State Management**: PHP sessions are used to maintain game state between requests.

3. **Dynamic UI**: The board is re-rendered after each move, reflecting the current game state.

4. **Move Validation**: The server validates all moves, ensuring game rules are followed.

5. **Multiple Captures**: The game supports consecutive captures in a single turn.

6. **King Pieces**: Special rules for king pieces are implemented.

## Installation and Requirements

- Requires PHP 7.0 or higher and a web server (e.g., Apache, Nginx)
- Clone the repository to your web server's document root
- Ensure your web server is configured to handle PHP files
- Access the game through a web browser

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.
