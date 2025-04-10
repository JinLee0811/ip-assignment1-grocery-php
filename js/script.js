// 검색창 지우기 버튼 기능
document.addEventListener("DOMContentLoaded", function () {
  const clearSearchButton = document.querySelector(".clear-search");
  if (clearSearchButton) {
    clearSearchButton.addEventListener("click", function () {
      const searchInput = document.querySelector('.search-box input[name="search"]');
      searchInput.value = "";
      // 폼 제출하여 모든 상품 표시
      document.querySelector(".search-box form").submit();
    });
  }
});
