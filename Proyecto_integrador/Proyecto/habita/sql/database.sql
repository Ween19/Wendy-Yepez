-- CREDENCIALES DE PRUEBA
--   Admin:   username = admin email = 'admin'     contraseña = '123456'
--   Usuario: username = usuario email = 'usuario@habita.com'  contraseña = '123456'


DROP DATABASE IF EXISTS habita_db;
CREATE DATABASE habita_db; 
USE habita_db;

CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE permisos (
    id_permiso INT AUTO_INCREMENT PRIMARY KEY,
    id_rol INT NOT NULL,
    permiso VARCHAR(50) NOT NULL,
    CONSTRAINT fk_permiso_rol
        FOREIGN KEY (id_rol)
        REFERENCES roles(id_rol)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,   
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    id_rol INT NOT NULL DEFAULT 2,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    email_verificado BOOLEAN DEFAULT FALSE,
    token_recuperacion VARCHAR(255) NULL,
    ultimo_acceso DATETIME NULL,
    estado TINYINT DEFAULT 1,
    CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

CREATE TABLE categorias (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre_cat VARCHAR(100) NOT NULL,
    es_popular BOOLEAN DEFAULT FALSE,
    icono VARCHAR(50) NULL,
    id_usuario INT NULL,
    categoria_padre_id INT NULL,
    CONSTRAINT fk_categoria_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_categoria_padre
        FOREIGN KEY (categoria_padre_id)
        REFERENCES categorias(id_categoria)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE habitos (
    id_habito INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_categoria INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    clase_habito ENUM('consciente','inconsciente') NOT NULL,
    direccion_habito ENUM('construir','romper') NOT NULL,
    dificultad ENUM('baja','media','alta') NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    orden INT DEFAULT 0,
    estado TINYINT DEFAULT 1,   -- 1 = activo, 0 = inactivo 
    fecha_eliminacion DATETIME NULL,
    eliminado_por INT NULL,
    fecha_ultima_recaida DATE NULL,   -- solo para habitos 'romper'
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_habito_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_habito_categoria
        FOREIGN KEY (id_categoria)
        REFERENCES categorias(id_categoria)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT fk_habito_eliminado_por
        FOREIGN KEY (eliminado_por)
        REFERENCES usuarios(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE
);

CREATE TABLE metas_config (
    id_meta INT AUTO_INCREMENT PRIMARY KEY,
    id_habito INT NOT NULL,
    frecuencia ENUM('diaria','semanal','personalizada') NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    unidad VARCHAR(30) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    recordatorio TIME NULL,
    CONSTRAINT fk_meta_habito
        FOREIGN KEY (id_habito)
        REFERENCES habitos(id_habito)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE metas_dias (
    id_meta INT NOT NULL,
    dia ENUM('L','M','X','J','V','S','D') NOT NULL,
    PRIMARY KEY (id_meta, dia),
    CONSTRAINT fk_meta_dia
        FOREIGN KEY (id_meta)
        REFERENCES metas_config(id_meta)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE historial_habitos (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_habito INT NULL,
    id_usuario INT NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NULL,
    valor_real DECIMAL(10,2) NULL,
    nota_emocional TEXT NULL,
    intensidad TINYINT NULL,
    es_retroactivo BOOLEAN DEFAULT FALSE,
    disparador_detectado VARCHAR(100) NULL,
    estado TINYINT NOT NULL DEFAULT 0,   
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historial_habito
        FOREIGN KEY (id_habito)
        REFERENCES habitos(id_habito)
        ON DELETE SET NULL
        ON UPDATE CASCADE,
    CONSTRAINT fk_historial_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE bitacora_auditoria (
    id_audit INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    accion ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    tabla_pk VARCHAR(100) NOT NULL,
    valor_anterior TEXT NULL,
    valor_nuevo TEXT NULL,
    ip_origen VARCHAR(45) NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
 
CREATE INDEX idx_usuario_email ON usuarios(email);
CREATE INDEX idx_habito_usuario ON habitos(id_usuario);
CREATE INDEX idx_habito_categoria ON habitos(id_categoria);
CREATE INDEX idx_meta_habito ON metas_config(id_habito);
CREATE INDEX idx_historial_usuario ON historial_habitos(id_usuario);
CREATE INDEX idx_historial_habito ON historial_habitos(id_habito);
CREATE INDEX idx_historial_fecha ON historial_habitos(fecha);
CREATE INDEX idx_auditoria_usuario ON bitacora_auditoria(id_usuario);


INSERT INTO roles (nombre_rol) VALUES ('admin'), ('usuario');

INSERT INTO permisos (id_rol, permiso) VALUES
(1, 'crear_usuario'),
(1, 'editar_usuario'),
(1, 'eliminar_usuario'),
(1, 'ver_auditoria'),
(1, 'gestionar_habitos'),
(1, 'ver_reportes');

INSERT INTO permisos (id_rol, permiso) VALUES
(2, 'crear_habito'),
(2, 'editar_habito'),
(2, 'eliminar_habito'),
(2, 'ver_reportes');

INSERT INTO categorias (nombre_cat, es_popular, icono) VALUES
('Salud', 1, 'heart'),
('Dieta', 1, 'apple'),
('Bienestar', 1, 'smile'),
('Productividad', 1, 'briefcase'),
('Pasiones', 1, 'star');


