CREATE DATABASE IF NOT EXISTS recetas_cocina
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

CREATE TABLE Usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Crear tabla Categoria
CREATE TABLE Categoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

-- Crear tabla Receta
CREATE TABLE Receta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    ingredientes TEXT,
    instrucciones TEXT,
    calorias INT,
    carbohidratos INT,
    proteinas INT,
    grasas INT,
    categoria_id INT,
    FOREIGN KEY (categoria_id) REFERENCES Categoria(id)
);

-- Añadir columna para almacenar ruta/nombre de imagen (si la tabla ya existe ejecutar el ALTER TABLE en tu DB)
ALTER TABLE Receta
ADD COLUMN imagen VARCHAR(255) NULL AFTER grasas;

-- Crear tabla Comentario
CREATE TABLE Comentario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto TEXT NOT NULL,
    fecha DATE NOT NULL,
    usuario_id INT,
    receta_id INT,
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id),
    FOREIGN KEY (receta_id) REFERENCES Receta(id)
);

insert into categoria (nombre) values 
('Postres'),
('Ensaladas'),
('Carnes');

insert into usuario (nombre_usuario, email, password) values 
('Guille123', 'guille123@gmail.com', '1234'),
('Matias123', 'matiasgo728@gmail.com', 'abcd');

INSERT INTO receta 
(nombre, descripcion, ingredientes, instrucciones, calorias, carbohidratos, proteinas, grasas, categoria_id) 
VALUES 
('Ensalada César', 
 'Clásica ensalada con pollo y aderezo César', 
 'Lechuga, pollo, queso parmesano, crutones, aderezo César',
 'Cortar la lechuga, cocinar el pollo, mezclar todo con el aderezo',
 350, 20, 25, 15, 2);

INSERT INTO Comentario (texto, fecha, usuario_id, receta_id) VALUES
('Muy rica y fácil de hacer!', '2025-09-09', 1, 1),
('Ideal para un almuerzo rápido.', '2025-09-09', 2, 1),
('La probé y me salió espectacular!', '2025-09-09', 1, 1);

select *from usuario;
select *from categoria;
select *from receta;
select *from comentario;
