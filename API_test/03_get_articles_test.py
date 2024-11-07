
import requests, pytest
from common import get_id, dict_in_list, article_id_in_list, HOST

acc_articles = [
    {"id": 1, "auth": 2},
    {"id": 4, "auth": 0},
    ]


other_articles = [0,2,3,5,6,7]

def test_ok_positive():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    j = response.json()
    assert isinstance(j, list)
    for a in acc_articles:
        assert dict_in_list(j, {
            "id": a["id"],
            "author_id": a["auth"],
            "approved": "yes",
            "title": f"Testovaci clanek {a['id']+1}",
            "descr":  'Testovaci clanek pro testovani',
            "key-words": 'test, testovani',
        })

def test_ok_negative():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    j = response.json()
    assert isinstance(j, list)
    for a in other_articles:
        assert not article_id_in_list(j, a)
