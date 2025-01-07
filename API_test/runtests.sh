#!/bin/bash

# don't forget to upload ../SQL_Scripts/api_test_data.sql to a *fresh* db, previously unchanged (only with setup tables)

cp test-article.pdf ../web/Articles/test1.pdf
cp test-article.pdf ../web/Articles/test5.pdf
cp test-article.pdf ../web/Articles/test7.pdf

pytest -v > result.txt

#rm copies articles
rm ../web/Articles/test1.pdf
rm ../web/Articles/test5.pdf
rm ../web/Articles/test7.pdf 2>/dev/null # gets deleted in 07_delete_article_test.py