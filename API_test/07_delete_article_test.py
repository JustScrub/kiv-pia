import requests, os
from common import get_id, article_id_in_list, HOST

def test_no_param():
    response = requests.get(f'{HOST}/api.php?service=delete_article', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 400
    assert "Missing query parameter" in response.json()["message"]

def test_noexist():
    response = requests.get(f'{HOST}/api.php?service=delete_article&id=999', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 404
    assert "Article not found" in response.json()["message"]

def test_not_own():
    response = requests.get(f'{HOST}/api.php?service=delete_article&id=6', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 403
    assert  "You can only delete your own articles" in response.json()["message"]

def test_ok():
    response = requests.get(f'{HOST}/api.php?service=delete_article&id=8', 
                            headers={'Authorization': get_id("rmar")})
    assert response.status_code == 200
    assert not os.path.exists("../Articles/test7.pdf")
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&login=rmar', 
                            headers={'Authorization': get_id()})
    assert not article_id_in_list(response.json(),8), response.text

if __name__ == "__main__":
    test_no_param()
    test_noexist()
    test_not_own()
    test_ok()
    print("All tests passed!")