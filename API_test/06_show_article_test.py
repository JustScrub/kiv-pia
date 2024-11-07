import requests, pytest
from common import get_id, HOST

def test_no_param():
    response = requests.get(f'{HOST}/api.php?service=show_article', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 400
    assert "Missing query parameter" in response.json()["message"]

def test_db_noexist():
    response = requests.get(f'{HOST}/api.php?service=show_article&id=noexist', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 404
    assert "Article not found" in response.json()["message"]
    assert response.json()["redirect"] == "/api.php?service=get_articles"

def test_file_noexist():
    response = requests.get(f'{HOST}/api.php?service=show_article&id=1', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 404
    assert "Article file not found" in response.json()["message"]
    assert response.json()["redirect"] == "/api.php?service=get_articles"

@pytest.mark.parametrize("id", [0,4])
def test_ok():
    response = requests.get(f'{HOST}/api.php?service=show_article&id={id}', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    assert response.headers["Content-Type"] == "application/pdf"
    assert response.headers["Content-Disposition"] == f'attachment; filename="Testovaci clanek {id+1}".pdf'
    with open("./test-article.pdf", "rb") as f:
        assert response.content == f.read()