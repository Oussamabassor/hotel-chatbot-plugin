(function ($) {
  class HotelChatbot {
    constructor(element) {
      this.element = element;
      this.hotelId = element.data("hotel-id");
      this.messagesContainer = element.find(".chat-messages");
      this.input = element.find(".chat-text");
      this.sendButton = element.find(".chat-send");
      this.bindEvents();
    }

    bindEvents() {
      this.sendButton.on("click", () => this.sendMessage());
      this.input.on("keypress", (e) => {
        if (e.which === 13) this.sendMessage();
      });
    }

    addMessage(message, isUser = false) {
      const messageDiv = $("<div>")
        .addClass("chat-message")
        .addClass(isUser ? "user-message" : "bot-message")
        .text(message);
      this.messagesContainer.append(messageDiv);
      this.messagesContainer.scrollTop(this.messagesContainer[0].scrollHeight);
    }

    sendMessage() {
      const message = this.input.val().trim();
      if (!message) return;

      this.addMessage(message, true);
      this.input.val("").focus();

      $.ajax({
        url: hotelChatbotAjax.ajaxurl,
        type: "POST",
        data: {
          action: "hotel_chatbot_message",
          nonce: hotelChatbotAjax.nonce,
          message: message,
          hotel_id: this.hotelId,
        },
        success: (response) => {
          if (response.success && response.data) {
            this.addMessage(response.data.response);
          }
        },
        error: () => {
          this.addMessage("Sorry, there was an error processing your message.");
        },
      });
    }
  }

  $(document).ready(() => {
    $(".hotel-chatbot").each((i, element) => {
      new HotelChatbot($(element));
    });
  });
})(jQuery);
