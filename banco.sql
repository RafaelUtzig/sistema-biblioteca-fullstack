DROP DATABASE IF EXISTS biblioteca;

CREATE DATABASE biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca;

CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,  
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL UNIQUE,
  descricao VARCHAR(255)
);

CREATE TABLE livros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(150) NOT NULL,
  autor VARCHAR(100) NOT NULL,
  ano SMALLINT,
  isbn VARCHAR(17),
  preco DECIMAL(8,2),
  status ENUM('disponivel','emprestado') DEFAULT 'disponivel',
  categoria_id INT NOT NULL,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

INSERT INTO categorias (nome, descricao) VALUES
('Romance', 'Obras de ficção narrativa'),
('Tecnologia', 'Programação e computação'),
('História', 'Livros históricos'),
('Fantasia', 'Mundos imaginários');

INSERT INTO livros (titulo, autor, ano, isbn, preco, status, categoria_id) VALUES
('Dom Casmurro', 'Machado de Assis', 1899, '978-85-359-0277-5', 29.90, 'disponivel', 1),
('Clean Code', 'Robert C. Martin', 2008, '978-01-323-5088-4', 89.90, 'disponivel', 2),
('Sapiens', 'Yuval Noah Harari', 2011, '978-85-254-3247-0', 59.90, 'emprestado', 3),
('O Hobbit', 'J.R.R. Tolkien', 1937, '978-85-780-0149-1', 44.90, 'disponivel', 4);