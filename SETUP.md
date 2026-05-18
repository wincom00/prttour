# prttour_myprt — Git / 환경 설정 가이드

이 문서는 로컬 작업 디렉토리 `d:\www\prttour_myprt` 를 GitHub
(`https://github.com/wincom00/prttour`) 에 안전하게 올리기 위한
환경 설정과 커밋 절차를 정리한 것이다.

---

## 1. 사전 점검 (실행 전 반드시 확인)

| 항목 | 명령 | 기대 결과 |
|------|------|----------|
| 작업 디렉토리 | `cd d:\www\prttour_myprt` | 프롬프트가 해당 경로 |
| git 저장소 여부 | `git status` | `On branch main` |
| 리모트 | `git remote -v` | `origin  https://github.com/wincom00/prttour` |
| .gitignore 인코딩 | `file .gitignore` (Git Bash) | `ASCII text` 또는 `UTF-8 text` |

> `.gitignore` 가 `data` 로 표시되면 UTF-16 으로 잘못 저장된 것이다.
> Windows 메모장으로 저장하지 말고 VSCode 에서 **UTF-8** 로 다시 저장해야 한다.

---

## 2. 민감 정보 분리 — DB 자격증명

DB 비밀번호가 들어있는 `include/dbconn.php` 는 **절대 GitHub 에 올리지 않는다.**

- `include/dbconn.php` → `.gitignore` 로 제외 (실제 운영 자격증명)
- `include/dbconn.sample.php` → 커밋 (placeholder 만 들어있는 템플릿)

새 환경에서 셋업할 때:
```powershell
Copy-Item include/dbconn.sample.php include/dbconn.php
# 그 다음 dbconn.php 를 열어 실제 DB host/user/password/dbname 입력
```

---

## 3. .gitignore 전략

`d:\www\prttour_myprt\.gitignore` 에 정리되어 있다. 카테고리:

| 카테고리 | 패턴 예시 | 이유 |
|----------|----------|------|
| DB / 자격증명 | `include/dbconn.php`, `.env`, `*.pem`, `*.key` | 보안 |
| Composer | `vendor/`, `composer.lock` | 서버에서 `composer install` |
| 백업 파일 | `bakups/`, `*.bak`, `*_bak.php`, `*_[0-9]{6}.php` | git history 가 백업 역할 |
| 레거시 구버전 | `base_reservation_m_*.php`, `index_*.php`, `login_old.php` 등 | 파일명에 날짜/접미사 붙은 옛 버전 |
| 대용량 바이너리 | `*.sql`, `*.pptx`, `*.xlsx`, `*.zip` 등 | repo 비대화 방지 |
| 미디어 / 업로드 | `upload/`, `product_img/`, `email_hist/`, `news/img_*/`, `sound/`, `video/` | 사용자 업로드물 |
| 로그 / 임시 | `*.log`, `nohup.out`, `*.tmp`, `tmp.html` | 런타임 산출물 |
| IDE / OS | `.vscode/`, `.idea/`, `.DS_Store`, `Thumbs.db` | 개인 환경 |
| Claude / AI 도구 | `.claude/`, `AGENTS.md` | 협업 도구 산출물 |
| 테스트 스크립트 | `test1.php`, `test55.php` 등 | 일회성 검증 파일 |

검증 명령:
```bash
# 제외되어야 할 파일들 확인 (출력이 있어야 정상)
git check-ignore -v include/dbconn.php prtdb_100925.sql nohup.out bakups/

# 추적되어야 할 파일들 확인 (출력이 없어야 정상)
git check-ignore -v include/dbconn.sample.php base_reservation_m.php composer.json
```

---

## 4. 초기 커밋 절차

```powershell
# 1) 현재 staged 된 것 확인
git status

# 2) .gitignore 적용 후 추가될 파일/용량 미리보기
git ls-files -o --exclude-standard | Measure-Object   # 파일 수
git ls-files -o --exclude-standard | ForEach-Object { (Get-Item $_).Length } | Measure-Object -Sum  # 총 바이트

# 3) 전체 추가
git add -A

# 4) 다시 status 로 확인 — vendor/, *.sql, bakups/, dbconn.php 가 없어야 함
git status --short | Select-String -Pattern "dbconn\.php$|\.sql$|^A\s+vendor/|^A\s+bakups/"
# (위 출력이 비어야 OK)

# 5) 커밋
git commit -m "Initial commit: project sources (excluding DB creds, backups, media, large binaries)"
```

---

## 5. GitHub Push

리모트 `main` 에는 이미 `Initial commit` (README.md) 이 있고
로컬 `main` 과 공통 조상이 없다. 두 가지 선택:

### A. 리모트 README 를 가져와서 합치기 (안전)
```powershell
git pull origin main --allow-unrelated-histories
# 충돌 없으면 그대로 진행, 충돌 시 README.md 만 충돌 → 수동 해결
git push -u origin main
```

### B. 리모트 README 를 버리고 로컬 것으로 덮어쓰기 (간단)
```powershell
git push -u origin main --force
```

---

## 6. 이후 워크플로우

### 일상 커밋
```powershell
git add <변경한 파일>
git commit -m "설명"
git push
```

### 새 환경/서버에서 클론
```powershell
git clone https://github.com/wincom00/prttour d:\www\prttour_myprt
cd d:\www\prttour_myprt
Copy-Item include/dbconn.sample.php include/dbconn.php
# dbconn.php 편집해서 실제 DB 정보 입력
# composer install   # composer.json 이 있는 경우
```

### 새 대용량 파일이 들어왔을 때
```powershell
# 100MB 넘는 파일이 staged 되면 push 실패함
git status --short | Select-String -Pattern "\.(pptx|sql|xlsx|zip)$"
# 발견되면 .gitignore 에 추가
```

---

## 7. 주의사항

- **메모장 사용 금지** — `.gitignore`, `dbconn.sample.php` 등을 Windows 메모장으로 저장하면 UTF-16 BOM 이 붙어 git 이 읽지 못한다. VSCode 또는 PowerShell 의 `Out-File -Encoding utf8NoBOM` 사용.
- **`git add .` 보다 `git add -A`** — 삭제도 함께 반영.
- **`git push --force` 는 main 에서 주의** — 다른 협업자가 있으면 작업 손실 위험.
- **`dbconn.php` 가 한 번이라도 push 되면 비밀번호 회전 필요** — git history 에 영구히 남는다.
