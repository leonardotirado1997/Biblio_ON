CREATE DATABASE bibli_on
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bibli_on;

-- tipo: usuario apenas adquire livros; funcionario e admin cadastram o acervo.
-- setor do funcionario: acervo, catalogacao, atendimento, referencia, administrativo.
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('usuario', 'funcionario', 'admin') NOT NULL DEFAULT 'usuario',
    setor VARCHAR(80) NULL,
    matricula VARCHAR(40) NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE livros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    autor VARCHAR(100) NOT NULL,
    descricao TEXT,
    categoria VARCHAR(80),
    preco DECIMAL(10,2) DEFAULT 0.00,
    imagem VARCHAR(255),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    livro_id INT NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(30) DEFAULT 'Confirmado',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (livro_id) REFERENCES livros(id)
);

INSERT INTO livros (titulo, autor, descricao, categoria, preco, imagem)
VALUES
('Dom Casmurro', 'Machado de Assis',
 'Um clássico da literatura brasileira que conta a história de Bentinho e Capitu.',
 'Romance', 29.90, 'dom-casmurro.jpg'),

('O Pequeno Príncipe', 'Antoine de Saint-Exupéry',
 'Uma história sobre amizade, amor e descobertas.',
 'Infantil', 24.90, 'pequeno-principe.jpg'),

('A Revolução dos Bichos', 'George Orwell',
 'Uma fábula sobre poder, política e sociedade.',
 'Ficção', 32.90, 'revolucao-dos-bichos.jpg');