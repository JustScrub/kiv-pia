-- password: test
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, heslo) values (0,'SuperAdmin', 'SuperAdmin', 'superadmin@test.com', 1, 'superadmin', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, heslo) values (1,'Admin', 'Admin', 'admin@test.com', 2, 'admin', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, heslo) values (2,'Test', 'Test', 'test@test.com', 4, 'test', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, ban, heslo) values (3,'Banned', 'Banned', 'banned@test.com', 4, 'banned', true, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');

insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, ban, heslo) values (4,'ToBan', 'ToBan', 'toban0@test.com', 4, 'toban0', false, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, ban, heslo) values (5,'ToBan', 'ToBan', 'toban1@test.com', 4, 'toban1', false, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, ban, heslo) values (6,'ToBan', 'ToBan', 'toban2@test.com', 4, 'toban2', false, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');

insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, heslo) values (7,'RemoveArticle', 'RemoveArticle', 'rmar@test.com', 4, 'rmar', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (id, jmeno, prijmeni, email, id_pravo, login, heslo) values (8,'AddArticle', 'AddArticle', 'adar@test.com', 4, 'adar', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');

insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (0, 2, 'Testovaci clanek 1', 'test1.pdf', 'test, testovani', 'Testovaci clanek pro testovani', 0, null);
insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (1, 2, 'Testovaci clanek 2', 'noexist.pdf', 'test, testovani', 'Testovaci clanek pro testovani', 1, null);
insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (2, 2, 'Testovaci clanek 3', '--', 'test, testovani', 'Testovaci clanek pro testovani', 2, null);

insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (3, 1, 'Testovaci clanek 4', '--', 'test, testovani', 'Testovaci clanek pro testovani', 0, null);

insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (4, 0, 'Testovaci clanek 5', 'test5.pdf', 'test, testovani', 'Testovaci clanek pro testovani', 1, null);

insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (5, 3, 'Testovaci clanek 6', '--', 'test, testovani', 'Testovaci clanek pro testovani', 0, null);
insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (6, 3, 'Testovaci clanek 7', '--', 'test, testovani', 'Testovaci clanek pro testovani', 2, null);

insert into clanek (id_clanek, id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values (7, 7, 'Testovaci clanek 8', 'test7.pdf', 'test, testovani', 'Testovaci clanek pro smazani', 2, null);