// ============================================================
//  TRIKI (Tres en Línea) — C++17
//  Autor: Webting
//  Compilar:  g++ -std=c++17 -O2 -o triki triki.cpp
//  Ejecutar:  ./triki   (Linux/Mac)   |   triki.exe  (Windows)
// ============================================================

#include <iostream>
#include <string>
#include <vector>
#include <limits>
#include <cstdlib>
#include <ctime>

using namespace std;

// ---------- Colores ANSI ----------
namespace Color {
    const string RESET   = "\033[0m";
    const string BOLD    = "\033[1m";
    const string RED     = "\033[31m";
    const string GREEN   = "\033[32m";
    const string YELLOW  = "\033[33m";
    const string BLUE    = "\033[34m";
    const string MAGENTA = "\033[35m";
    const string CYAN    = "\033[36m";
    const string GRAY    = "\033[90m";
    const string BG_BLUE = "\033[44m";
}

// ---------- Constantes del juego ----------
const char EMPTY = ' ';
const char HUMAN_X = 'X';
const char HUMAN_O = 'O';

// ============================================================
//  CLASE: Tablero
//  Encapsula el estado del tablero 3x3 y las operaciones.
// ============================================================
class Tablero {
private:
    char cells[3][3];

public:
    Tablero() { reset(); }

    void reset() {
        for (int i = 0; i < 3; ++i)
            for (int j = 0; j < 3; ++j)
                cells[i][j] = EMPTY;
    }

    bool isEmpty(int row, int col) const {
        return cells[row][col] == EMPTY;
    }

    bool place(int row, int col, char player) {
        if (row < 0 || row > 2 || col < 0 || col > 2) return false;
        if (!isEmpty(row, col)) return false;
        cells[row][col] = player;
        return true;
    }

    void undo(int row, int col) { cells[row][col] = EMPTY; }

    char get(int row, int col) const { return cells[row][col]; }

    // Devuelve true si hay una línea ganadora y guarda las coordenadas
    bool checkWin(char player, int winLine[3][2]) const {
        // Filas
        for (int i = 0; i < 3; ++i) {
            if (cells[i][0] == player && cells[i][1] == player && cells[i][2] == player) {
                winLine[0][0] = i; winLine[0][1] = 0;
                winLine[1][0] = i; winLine[1][1] = 1;
                winLine[2][0] = i; winLine[2][1] = 2;
                return true;
            }
        }
        // Columnas
        for (int j = 0; j < 3; ++j) {
            if (cells[0][j] == player && cells[1][j] == player && cells[2][j] == player) {
                winLine[0][0] = 0; winLine[0][1] = j;
                winLine[1][0] = 1; winLine[1][1] = j;
                winLine[2][0] = 2; winLine[2][1] = j;
                return true;
            }
        }
        // Diagonal principal
        if (cells[0][0] == player && cells[1][1] == player && cells[2][2] == player) {
            winLine[0][0] = 0; winLine[0][1] = 0;
            winLine[1][0] = 1; winLine[1][1] = 1;
            winLine[2][0] = 2; winLine[2][1] = 2;
            return true;
        }
        // Diagonal secundaria
        if (cells[0][2] == player && cells[1][1] == player && cells[2][0] == player) {
            winLine[0][0] = 0; winLine[0][1] = 2;
            winLine[1][0] = 1; winLine[1][1] = 1;
            winLine[2][0] = 2; winLine[2][1] = 0;
            return true;
        }
        return false;
    }

    bool isFull() const {
        for (int i = 0; i < 3; ++i)
            for (int j = 0; j < 3; ++j)
                if (cells[i][j] == EMPTY) return false;
        return true;
    }

    // Snapshot para la IA
    vector<vector<char>> snapshot() const {
        vector<vector<char>> v(3, vector<char>(3));
        for (int i = 0; i < 3; ++i)
            for (int j = 0; j < 3; ++j)
                v[i][j] = cells[i][j];
        return v;
    }
};

// ============================================================
//  CLASE: IA (Minimax perfecto)
// ============================================================
class IA {
public:
    // Devuelve el mejor movimiento {fila, col}
    pair<int,int> bestMove(const Tablero& t, char iaPlayer, char humanPlayer) {
        int bestScore = numeric_limits<int>::min();
        pair<int,int> best = {-1, -1};

        for (int i = 0; i < 3; ++i) {
            for (int j = 0; j < 3; ++j) {
                if (t.isEmpty(i, j)) {
                    Tablero copy = t;
                    copy.place(i, j, iaPlayer);
                    int score = minimax(copy, 0, false, iaPlayer, humanPlayer, numeric_limits<int>::min(), numeric_limits<int>::max());
                    if (score > bestScore) {
                        bestScore = score;
                        best = {i, j};
                    }
                }
            }
        }
        return best;
    }

private:
    // Minimax con poda Alpha-Beta
    int minimax(Tablero t, int depth, bool isMax, char iaPlayer, char humanPlayer, int alpha, int beta) {
        int winLine[3][2];
        if (t.checkWin(iaPlayer, winLine))    return 10 - depth;
        if (t.checkWin(humanPlayer, winLine)) return depth - 10;
        if (t.isFull())                       return 0;

        if (isMax) {
            int best = numeric_limits<int>::min();
            for (int i = 0; i < 3; ++i) {
                for (int j = 0; j < 3; ++j) {
                    if (t.isEmpty(i, j)) {
                        t.place(i, j, iaPlayer);
                        int val = minimax(t, depth + 1, false, iaPlayer, humanPlayer, alpha, beta);
                        t.undo(i, j);
                        best = max(best, val);
                        alpha = max(alpha, best);
                        if (beta <= alpha) return best;
                    }
                }
            }
            return best;
        } else {
            int best = numeric_limits<int>::max();
            for (int i = 0; i < 3; ++i) {
                for (int j = 0; j < 3; ++j) {
                    if (t.isEmpty(i, j)) {
                        t.place(i, j, humanPlayer);
                        int val = minimax(t, depth + 1, true, iaPlayer, humanPlayer, alpha, beta);
                        t.undo(i, j);
                        best = min(best, val);
                        beta = min(beta, best);
                        if (beta <= alpha) return best;
                    }
                }
            }
            return best;
        }
    }
};

// ============================================================
//  CLASE: Juego (controlador principal)
// ============================================================
class Juego {
private:
    Tablero tablero;
    IA ia;

    int victoriasX = 0;
    int victoriasO = 0;
    int empates    = 0;

    int modo = 0; // 1 = 2 jugadores, 2 = vs CPU

public:
    void menu() {
        while (true) {
            limpiarPantalla();
            cout << Color::CYAN << Color::BOLD;
            cout << "╔══════════════════════════════════════════╗\n";
            cout << "║            T R I K I   C + +             ║\n";
            cout << "╚══════════════════════════════════════════╝\n";
            cout << Color::RESET << "\n";
            cout << "  " << Color::YELLOW << "1." << Color::RESET << " Dos Jugadores (X vs O)\n";
            cout << "  " << Color::YELLOW << "2." << Color::RESET << " Jugar contra la CPU (IA)\n";
            cout << "  " << Color::YELLOW << "3." << Color::RESET << " Ver marcador\n";
            cout << "  " << Color::YELLOW << "4." << Color::RESET << " Salir\n\n";
            cout << "  Elige una opcion: ";

            int op;
            if (!(cin >> op)) {
                cin.clear();
                cin.ignore(numeric_limits<streamsize>::max(), '\n');
                continue;
            }

            switch (op) {
                case 1: modo = 1; jugar(); break;
                case 2: modo = 2; jugar(); break;
                case 3: verMarcador(); pausar(); break;
                case 4: limpiarPantalla();
                        cout << Color::GREEN << "\n  ¡Gracias por jugar Triki! 👋\n\n" << Color::RESET;
                        return;
                default: break;
            }
        }
    }

private:
    // ------------- Loop de una partida -------------
    void jugar() {
        char jugadorActual = HUMAN_X;
        int jugadas = 0;
        int winLine[3][2] = {{-1,-1},{-1,-1},{-1,-1}};
        char ganador = EMPTY;

        tablero.reset();

        while (true) {
            dibujarTablero(winLine);
            cout << "\n  Turno de: " << colorJugador(jugadorActual) << jugadorActual << Color::RESET << "\n";

            pair<int,int> mov;
            if (modo == 2 && jugadorActual == HUMAN_O) {
                cout << Color::MAGENTA << "  La CPU esta pensando...\n" << Color::RESET;
                mov = ia.bestMove(tablero, HUMAN_O, HUMAN_X);
            } else {
                mov = pedirMovimiento(jugadorActual);
            }

            if (mov.first == -2) { // el usuario pidió salir
                return;
            }

            tablero.place(mov.first, mov.second, jugadorActual);
            ++jugadas;

            if (tablero.checkWin(jugadorActual, winLine)) {
                ganador = jugadorActual;
                break;
            }
            if (tablero.isFull()) break;

            jugadorActual = (jugadorActual == HUMAN_X) ? HUMAN_O : HUMAN_X;
        }

        dibujarTablero(winLine);

        cout << "\n";
        if (ganador == HUMAN_X) {
            cout << Color::GREEN << Color::BOLD << "  ¡Gana X! 🎉\n" << Color::RESET;
            victoriasX++;
        } else if (ganador == HUMAN_O) {
            cout << Color::GREEN << Color::BOLD << "  ¡Gana O! 🎉\n" << Color::RESET;
            victoriasO++;
        } else {
            cout << Color::YELLOW << Color::BOLD << "  ¡Empate! 🤝\n" << Color::RESET;
            empates++;
        }

        cout << "\n  " << Color::CYAN << "[Enter]" << Color::RESET << " volver al menu...";
        cin.ignore(numeric_limits<streamsize>::max(), '\n');
        cin.get();
    }

    // ------------- Pedir movimiento al humano -------------
    pair<int,int> pedirMovimiento(char jugador) {
        while (true) {
            cout << "  Ingresa fila y columna (1-3) [ej: 2 3]  |  0 0 para salir: ";
            int r, c;
            if (!(cin >> r >> c)) {
                cin.clear();
                cin.ignore(numeric_limits<streamsize>::max(), '\n');
                cout << Color::RED << "  Entrada invalida.\n" << Color::RESET;
                continue;
            }
            if (r == 0 && c == 0) return {-2, -2};

            r--; c--;
            if (r < 0 || r > 2 || c < 0 || c > 2) {
                cout << Color::RED << "  Coordenadas fuera de rango (1-3).\n" << Color::RESET;
                continue;
            }
            if (!tablero.isEmpty(r, c)) {
                cout << Color::RED << "  Esa casilla ya esta ocupada.\n" << Color::RESET;
                continue;
            }
            return {r, c};
        }
    }

    // ------------- Render del tablero -------------
    void dibujarTablero(int winLine[3][2]) const {
        limpiarPantalla();
        cout << Color::CYAN << Color::BOLD;
        cout << "  ╔═══════════════════════════════╗\n";
        cout << "  ║          T R I K I            ║\n";
        cout << "  ╚═══════════════════════════════╝\n";
        cout << Color::RESET << "\n";

        cout << "      1   2   3\n";
        for (int i = 0; i < 3; ++i) {
            cout << "   " << (i + 1) << "  ";
            for (int j = 0; j < 3; ++j) {
                char c = tablero.get(i, j);
                bool esGanadora = false;
                for (int k = 0; k < 3; ++k)
                    if (winLine[k][0] == i && winLine[k][1] == j) esGanadora = true;

                if (esGanadora) cout << Color::BG_BLUE << Color::BOLD << " " << c << " " << Color::RESET;
                else            cout << " " << colorJugador(c) << c << Color::RESET << " ";

                if (j < 2) cout << Color::GRAY << "│" << Color::RESET;
            }
            cout << "\n";
            if (i < 2) cout << "     " << Color::GRAY << "───┼───┼───" << Color::RESET << "\n";
        }
        cout << "\n";
    }

    // ------------- Marcador -------------
    void verMarcador() const {
        limpiarPantalla();
        cout << Color::CYAN << Color::BOLD << "\n  📊  M A R C A D O R\n" << Color::RESET;
        cout << "  ─────────────────────────────\n";
        cout << "   Victorias " << Color::RED << "X" << Color::RESET << ":  " << victoriasX << "\n";
        cout << "   Victorias " << Color::BLUE << "O" << Color::RESET << ":  " << victoriasO << "\n";
        cout << "   Empates   :  " << empates << "\n";
        cout << "  ─────────────────────────────\n";
        if (victoriasX > victoriasO)      cout << Color::GREEN << "\n  🏆 Va ganando X\n" << Color::RESET;
        else if (victoriasO > victoriasX) cout << Color::GREEN << "\n  🏆 Va ganando O\n" << Color::RESET;
        else                              cout << Color::YELLOW << "\n  ⚖️  Empate técnico\n" << Color::RESET;
    }

    // ------------- Helpers -------------
    string colorJugador(char c) const {
        if (c == HUMAN_X) return Color::RED;
        if (c == HUMAN_O) return Color::BLUE;
        return Color::GRAY;
    }

    void limpiarPantalla() const {
#ifdef _WIN32
        system("cls");
#else
        system("clear");
#endif
    }

    void pausar() const {
        cout << "\n  " << Color::CYAN << "[Enter]" << Color::RESET << " continuar...";
        cin.ignore(numeric_limits<streamsize>::max(), '\n');
        cin.get();
    }
};

// ============================================================
//  MAIN
// ============================================================
int main() {
    srand(static_cast<unsigned>(time(nullptr)));
    Juego juego;
    juego.menu();
    return 0;
}