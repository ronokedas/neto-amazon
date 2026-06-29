-- MySQL 8.0 já possui função UUID() nativa
-- Apenas configuramos timezone e sql_mode

SET GLOBAL sql_mode = 'NO_ENGINE_SUBSTITUTION';
SET GLOBAL time_zone = '-03:00';