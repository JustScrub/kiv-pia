import requests, pytest
from common import get_id, dict_in_list, HOST

def test_no_param():
    response = requests.get(f'{HOST}/api.php?service=get_user_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 400
    assert "Missing query parameter" in response.json()["message"]

def test_noexist():
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&id=noexist', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 404
    assert "User not found" in response.json()["message"]
    assert response.json()["redirect"] == "/api.php?service=get_user"

@pytest.mark.parametrize("ar_data", [
    {"id": 0, "articles": [5], "appr": ["yes"]},
    {"id": 1, "articles": [4], "appr": ["pending"]},
    {"id": 2, "articles": [1, 2, 3], "appr": ["pending", "yes", "no"]},
    {"id": 3, "articles": [6, 7], "appr": ["pending", "no"]},
    ])
def test_ok(ar_data):
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&id={ar_data["id"]}', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    j = response.json()
    assert isinstance(j, list)
    assert len(j) == len(ar_data["articles"])
    for i in range(len(j)):
        assert dict_in_list(j, {
            "id": ar_data["articles"][i]-1,
            "author_id": ar_data["id"],
            "title": f"Testovaci clanek {ar_data['articles'][i]}",
            "descr":  'Testovaci clanek pro testovani',
            "key-words": 'test, testovani',
            "approved": ar_data["appr"][i],
            })
