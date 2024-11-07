-- password: test
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, heslo) values ('SuperAdmin', 'SuperAdmin', 'superadmin@test.com', 1, 'superadmin', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, heslo) values ('Admin', 'Admin', 'admin@test.com', 2, 'admin', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, heslo) values ('Test', 'Test', 'test@test.com', 4, 'test', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, ban, heslo) values ('Banned', 'Banned', 'banned@test.com', 4, 'banned', true, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');

insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, ban, heslo) values ('ToBan', 'ToBan', 'toban0@test.com', 4, 'toban0', false, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, ban, heslo) values ('ToBan', 'ToBan', 'toban1@test.com', 4, 'toban1', false, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, ban, heslo) values ('ToBan', 'ToBan', 'toban2@test.com', 4, 'toban2', false, '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');

insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, heslo) values ('RemoveArticle', 'RemoveArticle', 'rmar@test.com', 4, 'rmar', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');
insert into uzivatel (jmeno, prijmeni, email, id_pravo, login, heslo) values ('AddArticle', 'AddArticle', 'adar@test.com', 4, 'adar', '$2y$10$1ogze8kbzvxfCtUcfqbcuujt6gkJJm46EXci9fRAAZb9v6zNEMW1y');

insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 3, 'Testovaci clanek 1', 'test1.pdf', 'test, testovani', 'Testovaci clanek pro testovani', 0, null);
insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 3, 'Testovaci clanek 2', 'noexist.pdf', 'test, testovani', 'Testovaci clanek pro testovani', 1, null);
insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 3, 'Testovaci clanek 3', '-1', 'test, testovani', 'Testovaci clanek pro testovani', 2, null);

insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 2, 'Testovaci clanek 4', '-2', 'test, testovani', 'Testovaci clanek pro testovani', 0, null);

insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 1, 'Testovaci clanek 5', 'test5.pdf', 'test, testovani', 'Testovaci clanek pro testovani', 1, null);

insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 4, 'Testovaci clanek 6', '-3', 'test, testovani', 'Testovaci clanek pro testovani', 0, null);
insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 4, 'Testovaci clanek 7', '-4', 'test, testovani', 'Testovaci clanek pro testovani', 2, null);

insert into clanek (id_autor, nazev, nazev_souboru, klicova_slova, popis, schvalen, datum_schvaleni) values ( 8, 'Testovaci clanek 8', 'test7.pdf', 'test, testovani', 'Testovaci clanek pro smazani', 2, null);