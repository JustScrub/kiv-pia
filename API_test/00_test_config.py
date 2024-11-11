import pytest
import shutil, os

@pytest.fixture(scope='session')
def test_config():
    # execute ../SQL_Scripts/api_test_data.sql or create docker container with this data
    # copy test-article.pdf to ../Articles/test1.pdf, ../Articles/test5.pdf and ../Articles/test7.pdf

    shutil.copyfile("./test-article.pdf", "../Articles/test1.pdf")
    shutil.copyfile("./test-article.pdf", "../Articles/test5.pdf")
    shutil.copyfile("./test-article.pdf", "../Articles/test7.pdf")

    yield

    # remove ../Articles/test1.pdf, ../Articles/test5.pdf and ../Articles/test7.pdf
    os.remove("../Articles/test1.pdf")
    os.remove("../Articles/test5.pdf")
    try:
        os.remove("../Articles/test7.pdf") # this file is deleted in 07_delete_article_test.py
    except FileNotFoundError:
        ...
