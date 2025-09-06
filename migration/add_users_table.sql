CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Default admin user with password 'admin'
INSERT INTO `users` (`login`, `password`, `role`) VALUES ('admin', '$2y$10$If6.g2iP7CM9lI/pDEc2v.acx2/S3a5gO5tO.s2m5p3y4E9z1Q6/q', 'admin');
