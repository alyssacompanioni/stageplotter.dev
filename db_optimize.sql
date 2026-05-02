-- db_optimize.sql
-- Database optimization: remove unused table/columns, fix 3NF violation, enforce username uniqueness.
--
-- Run once against an existing stage_plotter database:
--   mysql -u <user> -p stage_plotter < db_optimize.sql
--
-- Changes:
--   1. DROP TABLE  plot_permission_pltperm          — unused, no code references it
--   2. DROP COLUMN plot_element_pele.type_pele      — 3NF violation: always derived from src_pele
--   3. DROP COLUMN input_list_channel_inplstch.id_pele_inplstch — unused nullable column, no FK
--   4. ADD UNIQUE  user_usr.username_usr             — uniqueness was app-only; enforce at DB level

USE `stage_plotter`;

-- 1. Drop unused permissions table (FK constraints must be dropped first)
ALTER TABLE `plot_permission_pltperm`
  DROP FOREIGN KEY `plot_permission_pltperm_ibfk_1`,
  DROP FOREIGN KEY `plot_permission_pltperm_ibfk_2`;

DROP TABLE `plot_permission_pltperm`;

-- 2. Remove type_pele — transitive dependency on src_pele (3NF violation)
ALTER TABLE `plot_element_pele`
  DROP COLUMN `type_pele`;

-- 3. Remove unused nullable column with no foreign key
ALTER TABLE `input_list_channel_inplstch`
  DROP COLUMN `id_pele_inplstch`;

-- 4. Enforce username uniqueness at the database level
ALTER TABLE `user_usr`
  ADD UNIQUE KEY `username_usr` (`username_usr`);
