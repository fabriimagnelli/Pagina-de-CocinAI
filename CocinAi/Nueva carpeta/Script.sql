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
    usuario_id INT,
    FOREIGN KEY (categoria_id) REFERENCES Categoria(id),
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id)
);

-- Añadir columna usuario_id a Receta y FK hacia Usuario (si no existe)
ALTER TABLE Receta
ADD COLUMN usuario_id INT NULL AFTER grasas;

ALTER TABLE Receta
ADD CONSTRAINT fk_receta_usuario FOREIGN KEY (usuario_id) REFERENCES Usuario(id);

-- Asignar usuario por defecto a recetas ya insertadas (ejemplo)
UPDATE Receta SET usuario_id = 1 WHERE id = 1;

-- Crear tabla Comentario
CREATE TABLE IF NOT EXISTS Comentario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto TEXT NOT NULL,
    fecha DATETIME NOT NULL,
    usuario_id INT NOT NULL,
    receta_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id),
    FOREIGN KEY (receta_id) REFERENCES Receta(id) ON DELETE CASCADE
);

-- Tabla para likes (me gusta) por receta
CREATE TABLE IF NOT EXISTS LikeReceta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    receta_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_like_usuario_receta (usuario_id, receta_id),
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (receta_id) REFERENCES Receta(id) ON DELETE CASCADE
);

-- Tabla para favoritos (guardados) por usuario
CREATE TABLE IF NOT EXISTS Favorito (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    receta_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_fav_usuario_receta (usuario_id, receta_id),
    FOREIGN KEY (usuario_id) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (receta_id) REFERENCES Receta(id) ON DELETE CASCADE
);

-- Tabla para seguimiento (seguir a usuarios)
CREATE TABLE IF NOT EXISTS Seguimiento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    followed_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ux_follow (follower_id, followed_id),
    FOREIGN KEY (follower_id) REFERENCES Usuario(id) ON DELETE CASCADE,
    FOREIGN KEY (followed_id) REFERENCES Usuario(id) ON DELETE CASCADE
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
