import requests, os
from common import get_id, is_subdict, HOST
# correct: call add_article endpoint with correct parameters, then upload_article endpoint where body is the pdf article

def test_add_no_param():
    response = requests.get(f'{HOST}/api.php?service=add_article', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 400
    assert "Missing request body" in response.json()["message"]

def test_add_bad_param():
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"abstract": "test"})
    assert response.status_code == 400
    assert "Missing body parameter" in response.json()["message"]

def test_add_new():
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Add article test", "descr": "Test", "key-words": "Test"})
    assert response.status_code == 200
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&id=8', 
                            headers={'Authorization': get_id("adar")})
    j = response.json()
    assert isinstance(j, list), response.text
    assert len(j) == 1, response.text
    assert is_subdict(j[0], {
        "title": "Add article test",
        "descr": "Test",
        "key-words": "Test",
        "author_id": 8,
        "approved": "pending"
    }), response.text
    id = j[0]["id"]
    # delete the article
    response = requests.get(f'{HOST}/api.php?service=delete_article&id={id}', 
                            headers={'Authorization': get_id("adar")})
    
def test_add_after_add():
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Add article test", "descr": "Test", "key-words": "Test"})
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Ovewritten", "descr": "OW", "key-words": "OW"})
    assert response.status_code == 200
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&id=8', 
                            headers={'Authorization': get_id("adar")})
    j = response.json()
    assert len(j) == 1
    assert is_subdict(j[0], {
        "title": "Ovewritten",
        "descr": "OW",
        "key-words": "OW",
        "author_id": 8,
        "approved": "pending"
    })
    id = j[0]["id"]
    # delete the article
    response = requests.get(f'{HOST}/api.php?service=delete_article&id={id}', 
                            headers={'Authorization': get_id("adar")})

def test_upload_no_param():
    response = requests.get(f'{HOST}/api.php?service=upload_article', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 400
    assert "Missing request body" in response.json()["message"]

def test_upload_no_add():
    with open("test-article.pdf", "rb") as f:
        data = f.read()
    response = requests.post(f'{HOST}/api.php?service=upload_article', 
                                 headers={'Authorization': get_id("adar"), 'Content-Type': 'application/pdf'},
                                 data=data)
    assert response.status_code == 404
    assert  "No article data found. Add article information first" in response.json()["message"]

def test_upload_ok():
    with open("test-article.pdf", "rb") as f:
        data = f.read()
    ardir_content_before = os.listdir("../Articles")
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Add article test", "descr": "Test", "key-words": "Test"})
    response = requests.post(f'{HOST}/api.php?service=upload_article', 
                                 headers={'Authorization': get_id("adar"), 'Content-Type': 'application/pdf'},
                                 data=data)
    assert response.status_code == 200
    ardir_content = os.listdir("../Articles")
    assert len(ardir_content) == len(ardir_content_before) + 1
    fname = [f for f in ardir_content if f not in ardir_content_before][0]
    with open("../Articles/" + fname, "rb") as f:
        assert f.read() == data
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&id=8', 
                            headers={'Authorization': get_id("adar")})
    j = response.json()
    assert len(j) == 1
    id = j[0]["id"]
    # show article
    response = requests.get(f'{HOST}/api.php?service=show_article&id={id}', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 200
    assert response.content == data

    # delete the article
    response = requests.get(f'{HOST}/api.php?service=delete_article&id={id}', 
                            headers={'Authorization': get_id("adar")})

if __name__ == "__main__":
    test_add_no_param()
    test_add_bad_param()
    test_add_new()
    test_add_after_add()
    test_upload_no_param()
    test_upload_no_add()
    test_upload_ok()
    print("All tests passed!")