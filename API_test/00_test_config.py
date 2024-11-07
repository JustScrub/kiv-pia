import pytest

@pytest.fixture(scope='session')
def test_config():
    ...
    # execute ../SQL_Scripts/api_test_data.sql or create docker container with this data
    # copy test-article.pdf to ../Articles/test1.pdf, ../Articles/test5.pdf and ../Articles/test7.pdf